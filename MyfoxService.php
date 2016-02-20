<?php

namespace GXApplications\HomeAutomationBundle;

use Doctrine\ORM\EntityManager;
use GXApplications\HomeAutomationBundle\Entity\Home;
use GXApplications\HomeAutomationBundle\Entity\Account;
use GXApplications\HomeAutomationBundle\Entity\MyfoxCommand;
use GXApplications\HomeAutomationBundle\ParameterException;
use GXApplications\HomeAutomationBundle\NotYetExecutedException;
use GXApplications\HomeAutomationBundle\NotFinishedExecutionException;
use GXApplications\HomeAutomationBundle\NotScheduledException;
use GXApplications\HomeAutomationBundle\Entity\MyfoxToken;
use Symfony\Component\Process\Process;
use Symfony\Component\DependencyInjection\ContainerInterface;
use JMose\CommandSchedulerBundle\Controller\DetailController;
use Symfony\Component\HttpFoundation\Request;
use JMose\CommandSchedulerBundle\Entity\ScheduledCommand;

class MyfoxService
{
	const ASAP = -1;
	const CANCEL = -2;
	
	/**
	 * @var EntityManager
	 */
	protected $em;
	
	protected $container;
	
	protected $clientId;
	
	protected $clientSecret;
	
	protected $httpEmulation;
	
	private $curlHandler = null;
	
	
	public function __construct(EntityManager $entityManager, ContainerInterface $container, $clientId, $clientSecret) {
		$this->em = $entityManager;
		$this->container = $container;
		$this->clientId = $clientId;
		$this->clientSecret = $clientSecret;
		$this->httpEmulation = !($clientId && strlen($clientId) > 0);
		if ($this->httpEmulation) $this->curlHandler = $this->createCurlHandler();
	}
	
	private function createCurlHandler($copyFrom = false) {
		if ($copyFrom) {
			$ch2 = curl_copy_handle($copyFrom);
			curl_close($copyFrom);
			return $ch2;
		}
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/48.0.2564.103 Safari/537.36');
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_COOKIESESSION, true);
		curl_setopt($ch, CURLOPT_COOKIEJAR, $this->container->getParameter('kernel.cache_dir').DIRECTORY_SEPARATOR."myfox-cookie.txt"); //could be empty, but cause problems on some hosts
		curl_setopt($ch, CURLOPT_COOKIEFILE, $this->container->getParameter('kernel.cache_dir').DIRECTORY_SEPARATOR."myfox-cookie.txt"); //could be empty, but cause problems on some hosts
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // FIXME: trouver plus securisee ?
		return $ch;
	}
	
	public static function execCurl($curlHandler, $logger, $url, $post = false, $postData = false) {
		curl_setopt($curlHandler, CURLOPT_URL, $url);
		curl_setopt($curlHandler, CURLOPT_POST, $post);
		if ($post && $postData) {
			$postData = http_build_query($postData);
			curl_setopt($curlHandler, CURLOPT_POSTFIELDS, $postData);
		}
		$answer = curl_exec($curlHandler);
		$error = curl_error($curlHandler);
		$errno = curl_errno($curlHandler);
		if ($answer === false || $error || $errno > 0) {
			$logger->error('Failure on cURL query on URL='.$url.' '.($post?'(POST)':'(GET)').'. cURL error Nbr'.$errno.': '.$error.". answer: ".$answer);
			throw new \Exception($errno);
		}
		$logger->info('cURL query on URL='.$url.' '.($post?'(POST)':'(GET)').' brings result: '.$answer);
		return $answer;
	}
	
	private function execCurlWrapper($url, $post = false, $postData = false) {
		return self::execCurl($this->curlHandler, $this->container->get('logger'), $url, (bool)$post, $postData);
	}
	
	
	
	
////////////////////////////
// Authentication methods //
////////////////////////////

	public function registerHome($login, $password) {
		$password = trim($password);
		if (!$this->httpEmulation) {
			list($token, $refreshToken, $validityTimestamp) = $this->retrieveToken($login, $password);
			
			$this->registerToken($token, $refreshToken, $login, $validityTimestamp);
		} else {
			$this->container->get('logger')->info('Trying to login to Myfox service through HTTP in Emulation mode...');
			try {
				$answer = $this->execCurlWrapper('https://myfox.me/login', true, array('username'=>$login, 'password'=>$password));
			} catch (\Exception $e) {
				$this->container->get('logger')->error('Failed to login. cURL error Nbr: '.$e->getMessage());
				throw new \Exception('Failed to login in HttpEmulation mode.');
			}
			
			$answer = json_decode($answer, true);
			if (array_key_exists("code", $answer) && $answer['code'] == 'KO') {
				$this->container->get('logger')->error('Failed to login. Bad Login/password.');
				throw new \Exception('Failed to login in HttpEmulation mode.');
			}
			
			//$this->curlHandler = self::createCurlHandler($this->curlHandler); // make a copy and close original.
			
			$validityTimestamp = time() + 118; // 2 min validity, with 2 seconds safely
			$this->registerToken(null, null, $login, $validityTimestamp, $password);
		}
	}
	
	// Only in API(JSON) mode
	private function retrieveToken($login, $password) {
		// TODO !106 : cf doc suivante pour authentifier et retourner les tokens :
		// https://github.com/Cyril-E/MyFoxHC2_Basic_fonctions/blob/master/myFox.php
		// en cas de fail, throw Exception.
		//throw new \Exception('Client_ID:'.$this->clientId." Client_Secret:".$this->clientSecret." Login:".$login." Password:".$password); // FIXME
		return array('token', 'refresh_token', 123456789); // FIXME
	}
	
	// Only in API(JSON) mode
	private function registerToken($token, $refreshToken, $login, $validityTimestamp, $password = false) {
		$t = new MyfoxToken();
		$t->setToken($token)->setRefreshToken($refreshToken)->setAccountLogin($login)->setValidity($validityTimestamp);
		if ($password) $t->setAccountPassword($password);
		$this->em->persist($t);
		$this->em->flush($t);
	}
	
	private function refreshTokenIfNeeded(Home $home) {
		$tokens = $this->em->getRepository('GXHomeAutomationBundle:MyfoxToken')->findBy(array('account_login' => $home->getAccount()->getAccountLogin()), array('validity' => 'DESC'));
		if (!$this->httpEmulation) {
			$now = time() - 60; // 1 minute security
			$validToken = null;
			
			foreach($tokens as $token) {
				/* @var $token MyfoxToken */
				if ($validToken != null) $this->em->remove($token); // old tokens, auto clean
				else {
					if ($token->getToken() == null || $token->getToken() == "") {
						$this->em->remove($token);
						continue;
					}
					if ($token->getValidity() < $now) {
						// TODO !106 : token perimee, on le refresh chez Myfox !
					}
					$validToken = $token;
				}
			}
			
			$this->em->flush();
			if (!$validToken) {
				$this->container->get('logger')->error('Myfox Authentication missing!');
				throw new \Exception('Myfox Authentication missing!'); 
			}
			return $validToken;
		} else {
			$validToken = null;
				
			foreach($tokens as $token) {
				/* @var $token MyfoxToken */
				if ($validToken != null) $this->em->remove($token); // old tokens, auto clean
				else {
					if ($token->getValidity() < time()) {
						
						// re-auth with current token
						$this->container->get('logger')->info('Trying to login again to Myfox service through HTTP in Emulation mode...');
						
						try {
							$answer = $this->execCurlWrapper('https://myfox.me/login', true, array('username'=>$token->getAccountLogin(), 'password'=>$token->getAccountPassword()));
						} catch (\Exception $e) {
							$this->container->get('logger')->error(' Failed to login again. cURL error Nbr: '.$e->getMessage());
							throw new \Exception('Failed to login again in HttpEmulation mode.');
						}
						
						$answer = json_decode($answer, true);
						if (array_key_exists("code", $answer) && $answer['code'] == 'KO') {
							$this->container->get('logger')->error("Failed to login again. Bad Login/password.");
							throw new \Exception("Failed to login again in HttpEmulation mode.");
						}
						
						//$this->curlHandler = self::createCurlHandler($this->curlHandler); // make a copy and close original.
							
						$validityTimestamp = time() + 118; // 2 min validity, with 2 seconds safely
						$token->setValidity($validityTimestamp);
						$this->em->persist($token);
					}
					$validToken = $token;
				}
			}
				
			$this->em->flush();
			if (!$validToken) throw new \Exception('Myfox Authentication missing!');
			$this->container->get('logger')->info('Myfox authentication succeed.');
			return $validToken;
		}
	}
	
	
	
	
/////////////////////
// Service methods //
/////////////////////

	/**
	 * Executes a Myfox command and return result. This call is synchronous (blocking).
	 * 
	 * @param integer $home_key The Myfox Home KEY value
	 * @param string $command The string with the raw command to execute. Please use MyfoxCommand::CMD_* for existing commands.
	 * @param array $parameters Associative array of parameters to inject into the command line. Please see MyfoxCommand::CMD_* for parameters on each command.
	 * @param boolean $checkCache If true, use an already equivalent and still valid command if exists. Else, create a new one.
	 * @param string $putInCache If true, the command is not just executed but also registered into the myfox_command table.
	 * @throws ParameterException
	 * @return string The result of the command, already parsed.
	 */
	public function playSync($home_key, $command, $parameters = array(), $checkCache = false, $putInCache = false) {
		$home = $this->em->getRepository('GXHomeAutomationBundle:Home')->findOneBy(array('home_key' => $home_key));
		if (!$home) {
			$this->container->get('logger')->error('Home Key not found');
			throw new ParameterException('Home Key not found');
		}

		$cmd = new MyfoxCommand($command, array_merge(array('%home_key%' => $home_key), $parameters), $this->httpEmulation);
		$this->container->get('logger')->info('playSync, MyfoxCommand built ('.$command.').');
		
		if ($checkCache) {
			$this->container->get('logger')->info('playSync, checking cache...');
			$cache = $cmd->getValidCache($this->em);
			if ($cache) {
				$this->container->get('logger')->info('playSync, cache found. Result returned: '.$cache->getResult());
				return $cache->getResult();
			}
		}
		
		$token = $this->refreshTokenIfNeeded($home);
		$rawResult = $this->_playSync($home, $cmd, $token, $putInCache);
		$parsedResult = $cmd->parseResult($rawResult, $this->container, $this->httpEmulation);
		$this->container->get('logger')->info('playSync, parsed result: '.$parsedResult);
		return $parsedResult;
	}
	
	/**
	 * Executes a registered Myfox command. This call is asynchronous. Most of the time, this call is made for ScheduledCommandBundle trigger.
	 * 
	 * @param integer $home_key The Myfox Home KEY value
	 * @param integer $command_id The command ID registered into myfox_command table
	 * @throws ParameterException
	 * @throws NotScheduledException
	 */
	public function playAsync($home_key, $command_id) {
		$home = $this->em->getRepository('GXHomeAutomationBundle:Home')->findOneBy(array('home_key' => $home_key));
		if (!$home) throw new ParameterException('Home Key not found');
		$command = $this->em->getRepository('GXHomeAutomationBundle:MyfoxCommand')->find($command_id);
		if (!$command) throw new NotScheduledException();
		$command->init();
		$this->em->flush();
		$token = $this->refreshTokenIfNeeded($home);
		$this->_playAsync($home, $command, $token);
	}
	
	/**
	 * Schedule a Myfox command for later asynchronous execution.
	 * 
	 * If $when is a timestamp, it will be converted into a cron expression.
	 * If $when is MyfoxService::ASAP, the command will be unscheduled and the run immediately.
	 * If $when is MyfoxService::CANCEL, the command will be unscheduled.
	 * 
	 * If ($useExistingCmd is true or if $when is MyfoxService::CANCEL) and if a scheduled command exists in the scheduler table, then we use it instead of creating a new one.
	 * 
	 * @param integer $home_key The Myfox Home KEY value
	 * @param string $command The string with the raw command to execute. Please use MyfoxCommand::CMD_* for existing commands.
	 * @param array $parameters Associative array of parameters to inject into the command line. Please see MyfoxCommand::CMD_* for parameters on each command.
	 * @param mixed $when A timestamp (in the futur > 1min later) OR a cron expression OR MyfoxService::ASAP OR MyfoxService::CANCEL
	 * @param boolean $useExistingCmd If true
	 * @throws \Exception
	 * @return boolean|integer The command ID, or True if $when == MyfoxService::CANCEL and cancelled successfully.
	 */
	public function schedule($home_key, $command, $parameters = array(), $when = self::ASAP, $useExistingCmd = false) {
		$home = $this->em->getRepository('GXHomeAutomationBundle:Home')->findOneBy(array('home_key' => $home_key));
		if (!$home) throw new \Exception('Home Key not found');
		$cmd = new MyfoxCommand($command, array_merge(array('%home_key%' => $home_key), $parameters), $this->httpEmulation);
		if ($useExistingCmd || $when == self::CANCEL) {
			$existing = $this->em->getRepository('GXHomeAutomationBundle:MyfoxCommand')->findOneBy(array('command' => $cmd->getCommand()), array('id' => 'DESC'));
			if ($existing) $cmd = $existing;
		}
		$token = $this->refreshTokenIfNeeded($home);
		return $this->_schedule($home, $cmd, $token, $when);
	}
	
	public function getCachedCommand($home_key, $command, $parameters = array()) {
		$home = $this->em->getRepository('GXHomeAutomationBundle:Home')->findOneBy(array('home_key' => $home_key));
		if (!$home) throw new ParameterException('Home Key not found');
		
		$cmd = new MyfoxCommand($command, array_merge(array('%home_key%' => $home_key), $parameters), $this->httpEmulation);
		
		return $cmd->getValidCache($this->em);
	}
	
	public function state($command_id, $clean_register = false) {
		$command = $this->em->getRepository('GXHomeAutomationBundle:MyfoxCommand')->find($command_id);
		if (!$command) throw new NotScheduledException();
		return $this->_state($command, $clean_register);
	}
	
	public function result($command_id, $clean_register = true) {
		$command = $this->em->getRepository('GXHomeAutomationBundle:MyfoxCommand')->find($command_id);
		if (!$command) throw new NotScheduledException();
		return $this->_result($command, $clean_register);
	}
	
	public function cancel($command_id) {
		$command = $this->em->getRepository('GXHomeAutomationBundle:MyfoxCommand')->find($command_id);
		if (!$command) throw new NotScheduledException();
		
		$state = $this->_state($command, false);
		if ($state == MyfoxCommand::PROGRESS) throw new NotFinishedExecutionException();
		
		// not executed yet OR FINISHED. Can clean register anyway.
		$this->em->remove($command);
		$this->em->flush($command);
		
		return ($state < MyfoxCommand::PROGRESS); // true if canceled, false if too late.
	}
	
	public function invalidateCachedCommand($home_key, $command, $parameters = array()) {
		$home = $this->em->getRepository('GXHomeAutomationBundle:Home')->findOneBy(array('home_key' => $home_key));
		if (!$home) throw new ParameterException('Home Key not found');
		
		$cmd = new MyfoxCommand($command, array_merge(array('%home_key%' => $home_key), $parameters), $this->httpEmulation);
		
		while(($cache = $cmd->getValidCache($this->em)) != null) {
			$this->em->remove($cache);
			$this->em->flush($cache);
		}
	}
	
	
	
	
/////////////////////
// Wrapper methods //
/////////////////////

	public function getAlarmStatus($home_key, $checkCache = true) {
		$result = $this->playSync($home_key, MyfoxCommand::CMD_GET_ALARM_STATUS, array(), $checkCache);
		return json_decode($result, true)['payload']['statusLabel'];
	}
	
	public function setAlarmStatus($home_key, $level, $pattern) {
		$conv = array('armed' => 4, 'partial' => 2, 'disarmed' => 1);
		if (!in_array($level, array_keys($conv))) throw new \Exception('level must be one value of [armed, partial, disarmed].');
		
		$actualStatus = $this->getAlarmStatus($home_key, false); // force re-query to avoid hack.
		
		if (($level == 'disarmed' && $actualStatus != 'disarmed') || ($level == 'partial' && $actualStatus == 'armed')) {
			
			$passwordEncoder = $this->container->get('security.password_encoder');
			$user = $this->container->get('security.context')->getToken()->getUser();
			if (!($user instanceof Account)) throw new \Exception('Cannot lower alarm level with user that is not a Myfox account.');
			
			$passwordValid = $passwordEncoder->isPasswordValid($user, $pattern);
			if (!$passwordValid) throw new \Exception('Cannot lower alarm level: wrong password.');
		}
			
		
		if ($this->httpEmulation) $level = $conv[$level];
		return $this->playSync($home_key, MyfoxCommand::CMD_SET_ALARM_STATUS, array('%level%' => $level));
	}
	
	
	
	
//////////////////////
// Internal methods //
//////////////////////

	private function _playSync(Home $home, MyfoxCommand $command, MyfoxToken $token, $putInCache = false) {
		$command->setState(MyfoxCommand::STATE_IN_PROGRESS);
		if (!is_numeric($command->getCommand())) { // is CMD case, not a MACRO
			if (!$this->httpEmulation) {
				// TODO !107 : jouer maintenant, en synchrone,et retourner le result en RAW sans traitement. En cas d'erreur, throw .*Exception
			} else {
				$this->container->get('logger')->info('Trying to send Http query to Myfox: '.$command->getCommand().'...');
				
				try {
					$rawResult = $this->execCurlWrapper('https://myfox.me'.$command->getCommand(), $command->isPost());
				} catch (\Exception $e) {
					$this->container->get('logger')->error('Failed to send Http query to Myfox from _playSync. cURL error Nbr: '.$e->getTraceAsString());
					throw new \Exception('Failed to send Http query to Myfox from _playSync.');
				}

				$this->container->get('logger')->debug('Http query answser: '.$rawResult);
			}
			
			if ($putInCache) {
				$command->dispatchResult($rawResult, $this->container, $this->em, $this->httpEmulation);
				$this->em->persist($command);
				$this->em->flush($command);
			}
		} else { // is MACRO case
			if (!$this->httpEmulation) {
				// TODO !107 : jouer maintenant, en synchrone,et retourner le result en RAW sans traitement. En cas d'erreur, throw .*Exception
			} else {
				$this->container->get('logger')->info('Trying to execute MACRO #'.$command->getCommand().'...');
				
				try {
					$rawResult = $command->executeMacro($this->curlHandler, $this->em, $this->httpEmulation, $this->container->get('logger'));
					if ($rawResult === false) {
						$this->container->get('logger')->error('Failed to execute MACRO for unknown reason.');
						throw new \Exception("Failed to execute MACRO for unknown reason.");
					}
				} catch (\Exception $e) {
					$this->container->get('logger')->error('Failed to execute MACRO. error: '.$e->getMessage());
					throw new \Exception('Failed to execute MACRO. error: '.$e->getMessage(), 500, $e);
				}
					
				$this->container->get('logger')->debug('MACRO #'.$command->getCommand().' answser: '.$rawResult);
			}
			
			if ($putInCache) {
				$command->dispatchResult($rawResult, $this->container, $this->em, $this->httpEmulation);
				$this->em->persist($command);
				$this->em->flush($command);
			}
		}
		return $rawResult;
	}
	
	private function _playAsync(Home $home, MyfoxCommand $command, MyfoxToken $token) {
		try {
			/*$result =*/ $this->_playSync($home, $command, $token, true);
		} catch (\Exception $e) {
			$this->container->get('logger')->error($e->getMessage());
			$this->container->get('logger')->error($e->getTraceAsString());
			$command->setState(MyfoxCommand::STATE_FAIL);
			foreach($command->getEquivalents() as $equivalent) {
				$this->em->remove($equivalent);
			}
		}
		$this->em->persist($command);
		$this->em->flush($command);
	}
	
	private function _schedule(Home $home, MyfoxCommand $command, MyfoxToken $token, $when = self::ASAP) {
		$command->setState(MyfoxCommand::STATE_WAITING);
		$this->em->persist($command);
		$this->em->flush($command);
		$this->em->refresh($command);
		
		$taskName = 'Myfox H.'.$home->getHomeKey().' C.'.$command->getId();
		$scheduledCommand = $this->em->getRepository('JMoseCommandSchedulerBundle:ScheduledCommand')->findOneBy(array('name' => $taskName), array('lastExecution' => 'DESC'));
		
		if ($when == self::ASAP) {
			if ($scheduledCommand) {
				$this->em->remove($scheduledCommand);
				$this->em->flush($scheduledCommand);
			}
			// http://symfony.com/doc/current/components/process.html#running-processes-asynchronously
			$process = new Process('php app/console myfox:play:asynchronous '.$command->getId());
			$process->start();
		} elseif($when == self::CANCEL) {
			if ($scheduledCommand) $this->em->remove($scheduledCommand);
			$this->em->remove($command);
			$this->em->flush();
			return true;
		} elseif (   (is_numeric($when) && $when > time()) || (!is_numeric($when) && strlen($when))   ) {
			if (!$scheduledCommand) $scheduledCommand = new ScheduledCommand();
			
			$removeScheduleOption = "";
			
			// case when is a timestamp. Will convert it into cron expression and add removeschedule option into the scheduler
			if (is_numeric($when)) {
				if ($when - time() < 60) $when = time() + 60; // minimal gap is 1 minute
				$when = date('i G j n w', $when); // minute - hour - day - month - day-of-week
				$removeScheduleOption = ' --removeschedule';
			}

			// schedule a command in CommandSchedulerBundle
			$scheduledCommand->setName($taskName)->setCommand('myfox:play:asynchronous');
			$scheduledCommand->setArguments('--home='.$home->getHomeKey().' --command_id='.$command->getId().$removeScheduleOption);
			$scheduledCommand->setCronExpression($when)->setLogFile('test_out.log');
			$scheduledCommand->setPriority(0)->setExecuteImmediately(0)->setDisabled(0);
			$scheduledCommand->setLastExecution(new \DateTime());
			$scheduledCommand->setLocked(false);
			$this->em->persist($scheduledCommand);
			$this->em->flush($scheduledCommand);
		} else throw ParameterException("'when' parameter should be in the futur, a cron expression, or ASAP const.");
		
		return $command->getId();
	}
	
	private function _state(MyfoxCommand $command, $clean_register = false) {
		$state = $command->getState();
		if ($clean_register) {
			$this->em->remove($command);
			$this->em->flush($command);
		}
		return $state;
	}
	
	private function _result(MyfoxCommand $command, $clean_register = true) {
		if ($command->getState() <= MyfoxCommand::STATE_WAITING_PARENT) throw new NotYetExecutedException();
		if ($command->getState() == MyfoxCommand::STATE_IN_PROGRESS) throw new NotFinishedExecutionException();
		if ($command->getState() < MyfoxCommand::STATE_SUCCESS) throw new \Exception('Myfox command error: unknown state.');
		
		$result = $command->getResult();
		if ($clean_register) {
			$this->em->remove($command);
			$this->em->flush($command);
		}
		return $result;
	}
}

class ParameterException extends \Exception { }
class NotYetExecutedException extends \Exception { }
class NotFinishedExecutionException extends \Exception { }
class NotScheduledException extends \Exception { }
