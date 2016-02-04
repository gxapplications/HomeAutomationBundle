<?php

namespace GXApplications\HomeAutomationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\EntityManager;
use Doctrine\Common\Collections\ArrayCollection;
use GXApplications\HomeAutomationBundle\MyfoxService;

/**
 * MyfoxCommand
 *
 * @ORM\Table(options={"engine"="MEMORY"})
 * @ORM\Entity
 */
class MyfoxCommand
{
	
	const STATE_NOT_INITIALIZED = 0;
	const STATE_WAITING = 10;
	const STATE_WAITING_PARENT = 15;
	const STATE_IN_PROGRESS = 20;
	const STATE_SUCCESS = 30;
	const STATE_FAIL = 40;
	
	// site / security
	const CMD_GET_ALARM_STATUS = "/site/%home_key%/security";
	const CMD_SET_ALARM_STATUS = "/site/%home_key%/security/set/%level%";
	
	// site / scenario
	const CMD_GET_SCENARIO_ITEMS = "/site/%home_key%/scenario/items";
	const CMD_SET_SCENARIO_PLAY = "/site/%home_key%/scenario/%scenario_id%/play";
	const CMD_SET_SCENARIO_ENABLE = "/site/%home_key%/scenario/%scenario_id%/enable";
	const CMD_SET_SCENARIO_DISABLE = "/site/%home_key%/scenario/%scenario_id%/disable";
	
	//TODO !109 : mettre les commandes ici... et dans le grand tableau dessous
	// https://api.myfox.me/dev/documentation
	
	const MACRO_SET_SCENARII_TEMPERATURE = 1;
	
	private $_CMD_PARAMETERS = null;
	private $_MACRO_PARAMETERS = null;
	
	private function buildParameters() {
		$this->_CMD_PARAMETERS = array(
				
			self::CMD_GET_ALARM_STATUS => array(
					'http' => array(
							'url' => "/home/%home_key%",
							'is_post' => false,
							'equivalents' => array(self::CMD_GET_SCENARIO_ITEMS, ),  // ici, il y aura bcp d'equivalences car on recoit le HTML de la page entière...
							'parser' => function($raw, $container) {
								// HTML: full page to parse
								// --> "payload": { "status": 1, "statusLabel": "disarmed" }
								\phpQuery::newDocument($raw);
								$el = \phpQuery::pq('div#dashboard > div.widget-protection > div.body > div#describe_zone:first');
								if ($el->hasClass('seclev1'))
									return array('status' => 1, 'statusLabel' => 'disarmed');
								if ($el->hasClass('seclev2'))
									return array('status' => 2, 'statusLabel' => 'partial');
								if ($el->hasClass('seclev4'))
									return array('status' => 4, 'statusLabel' => 'armed');
								return false; // failure case
							}
					),
					'validity' => 300
			),
			
			self::CMD_SET_ALARM_STATUS => array(
					'http' => array(
							'url' => "/widget/%home_key%/protection/seclev/%level%", // 1,2,4
							'is_post' => true,
							'equivalents' => array(),  // ici, il y aura bcp d'equivalences car on recoit le HTML de la page entière...
							'parser' => function($raw, $container) {
								return self::parseSimplePost($raw);
							}
					),
					'validity' => -1 // no cache for set action
			),
			
			self::CMD_GET_SCENARIO_ITEMS => array(
					'http' => array(
							'url' => "/home/%home_key%",
							'is_post' => false,
							'equivalents' => array(self::CMD_GET_ALARM_STATUS, ),
							'parser' => function($raw, $container) {
								// HTML: full page to parse
								// --> "payload": { "items": [ SCENARIO, SCENARIO, ... ] }
								// SCENARIO : { "scenarioId": 1234, "label": "Mon scenario", "typeLabel": ['onDemand'|'scheduled'|'onEvent'|'simulation'], "enabled": true }
								\phpQuery::newDocument($raw);
								$els = \phpQuery::pq('div#scenario_list tr');
								$scs = array();
								foreach($els as $el) {
									$sc = array();
									/* @var $el \phpQueryObject */
									// typeLabel
									$typeLabel = \phpQuery::pq('td > span.icon', $el);
									if ($typeLabel->hasClass('icon-scenariotype-1'))
										$sc['typeLabel'] = 'onDemand';
									elseif ($typeLabel->hasClass('icon-scenariotype-2'))
										$sc['typeLabel'] = 'scheduled';
									elseif ($typeLabel->hasClass('icon-scenariotype-3'))
										$sc['typeLabel'] = 'onEvent';
									else $sc['typeLabel'] = 'simulation';
									// label
									$sc['label'] = \phpQuery::pq('td > span.text', $el)->text();
									//scenarioId
									switch ($sc['typeLabel']) {
										case 'onDemand':
											$sc['scenarioId'] = preg_replace('/^.*\\//', "", \phpQuery::pq('td > a.smart', $el)->attr('href'));
											break;
										default:
											$sc['scenarioId'] = preg_replace('/^.*\\//', "", \phpQuery::pq('td span.buttons > span[data-value="1"]', $el)->attr('data-call'));
									}
									//enabled
									$enabled =  \phpQuery::pq('td > span.switch > input', $el)->val();
									if ($enabled === '0') $sc['enabled'] = false;
									if ($enabled === '1') $sc['enabled'] = true;
									$scs[] = $sc;
								}
								return array("items" => $scs);
							}
					),
					'validity' => 300
			),
			
			self::CMD_SET_SCENARIO_PLAY => array(
					'http' => array(
							'url' => "/widget/%home_key%/scenario/play/%scenario_id%",
							'is_post' => true,
							'equivalents' => array(),
							'parser' => function($raw, $container) {
								return self::parseSimplePost($raw);
							}
					),
					'validity' => -1
			),
			
			self::CMD_SET_SCENARIO_ENABLE => array(
					'http' => array(
							'url' => "/widget/%home_key%/scenario/on/%scenario_id%",
							'is_post' => true,
							'equivalents' => array(),
							'parser' => function($raw, $container) {
								return self::parseSimplePost($raw);
							}
					),
					'validity' => -1
			),
			
			self::CMD_SET_SCENARIO_DISABLE => array(
					'http' => array(
							'url' => "/widget/%home_key%/scenario/off/%scenario_id%",
							'is_post' => true,
							'equivalents' => array(),
							'parser' => function($raw, $container) {
								return self::parseSimplePost($raw);
							}
					),
					'validity' => -1
			),
			
			
		);
		
		$this->_MACRO_PARAMETERS = array(
			self::MACRO_SET_SCENARII_TEMPERATURE => array(
				'execute' => function($parameters, $curlHandler, $em, $httpEmulation, $logger) {

					$homeKey = $parameters['%home_key%'];
					$component = $em->getRepository('GXHomeAutomationBundle:Component')->find($parameters['%component_id%']);
					if (!$component) return json_encode(array('status'=>'KO'));
					$heatingDashboardComponent = $component->getHeatingDashboard();
					if (!$heatingDashboardComponent) return json_encode(array('status'=>'KO'));
					
					$newTemperature = $parameters['%value%'];
					foreach($parameters['%scenarii%'] as $scenario) {
						// For each scenario, 5 steps. we modifies temperature only.
						$step = 0;
						try {
							
							// step 1: GET to "https://myfox.me/scenario/%home_key%/manage/%scenario_id%/1"
							// -> label = $('form#scenarioForm input[name="label"]').val()
							// -> scData = $('form#scenarioForm input[name="scData"]').val()
							$step++;
							$rawResult = MyfoxService::execCurl($curlHandler, $logger, 'https://myfox.me/scenario/'.$homeKey.'/manage/'.$scenario.'/1', false);
							$nextValues = array('label' => "TODO", 'scData' => "TODO");
							// TODO !100 : parse PHPQuery for label and scData
							
							
							// step 2: POST to "https://myfox.me/scenario/%home_key%/manage/%scenario_id%/2" with label and scData
							// -> type = $('form#scenarioForm input[name="scenarioType"]').val()
							// -> scData = $('form#scenarioForm input[name="scData"]').val()
							$step++;
							$rawResult = MyfoxService::execCurl($curlHandler, $logger,
									'https://myfox.me/scenario/'.$homeKey.'/manage/'.$scenario.'/2',
									true, $nextValues); // FIXME: doit-on changer le Content-type de l'entete ? option curl pour ca si besoin.
							$nextValues = array('type' => "TODO", 'scData' => "TODO");
							// TODO !100 : parse PHPQuery for type and scData
							
							
							// step 3: POST to "https://myfox.me/scenario/%home_key%/manage/%scenario_id%/3" with type and scData
							// -> retrieve all fields with their attribute 'name' as field name that correpond to:
							//		input:hidden:not([disabled])
							//		input:checkbox[checked]:not([disabled])
							//		input:radio[checked]:not([disabled])
							//		input:text:not([disabled])
							//		(but modify those who are input:text[name*="[4]["][name*="][value]"]:not([disabled]) with newTemperature.
							$step++;
							// TODO !100
							
							
							// step 4: POST to "https://myfox.me/scenario/%home_key%/manage/%scenario_id%/4" with retrieved fields and modified temperature.
							// -> retrieve all fields with their attribute 'name' as field name that correpond to:
							//		input:hidden:not([disabled])
							//		input:checkbox[checked]:not([disabled])
							//		input:radio[checked]:not([disabled])
							//		input:text:not([disabled])
							$step++;
							// TODO !100
							
							// step 5: POST to "https://myfox.me/scenario/%home_key%/manage/%scenario_id%/5" with retrieved fields.
							// -> scData = $('form#scenarioForm input[name="scData"]').val() MAIS ON N'EN FAIT RIEN !
							$step++;
							// TODO !100
							
						} catch (\Exception $e) {
							$logger->error('Failed to send Http query #'.$step.' for MACRO_SET_SCENARII_TEMPERATURE.');
							throw new \Exception('Failed to send Http query #'.$step.' for MACRO_SET_SCENARII_TEMPERATURE.');
						}
					}
					
					// Update heatingDashboardComponent
					// convert "scenarii_minimal_temp" to "setScenariiMinimalTemp" to call the right setter.
					$setter = 'set'.str_replace(" ", "", ucwords(str_replace("_", " ", $parameters['%heating_dashboard_attribute%'])));
					$heatingDashboardComponent->$setter($newTemperature);
					$em->persist($heatingDashboardComponent);
					$em->flush($heatingDashboardComponent);
					
					return json_encode(array('status'=>'OK'));
				}
			),
				
		);
	}
	
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="state", type="smallint")
     */
    private $state = 0;
    
    /**
     * @var string
     *
     * @ORM\Column(name="command", type="string", length=512)
     */
    private $command;
    
    /**
     * @var string
     *
     * @ORM\Column(name="result", type="string", length=18000, nullable=true)
     */
    private $result = null;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="cmd_idx", type="smallint")
     */
    private $cmdIdx = -1;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="valid_until", type="integer")
     */
    private $validUntil = 0;
    
    /**
     * @ORM\ManyToOne(targetEntity="MyfoxCommand", inversedBy="equivalents")
     * @ORM\JoinColumn(name="original_command", referencedColumnName="id", onDelete="SET NULL")
     */
    private $originalCommand = null;
    
    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="MyfoxCommand", mappedBy="original_command", cascade={"persist"}, orphanRemoval=false)
     */
    private $equivalents;
    
    /**
     * @var string
     *
     * @ORM\Column(name="macro_parameters", type="string", length=1024, nullable=true)
     */
    private $macro_parameters = null;
    
    /**
     * 
     * @param string $command Optional command string for auto init
     * @param Array $parameters Optional command arguments (associative array)
     * @param boolean $httpEmulation True if using HTTP emulation instead of API calls.
     * @param boolean $createEquivalents True to create automatically all myfoxCommand equivalences that will share the same raw result. True only for HttpEmulated mode.
     * 
     */
    public function __construct($command = false, $parameters = array(), $httpEmulation = false, $createEquivalents = true)
    {
    	$this->equivalents = new ArrayCollection();
    	$this->buildParameters();
    	
    	if ($command && !is_numeric($command)) {
    		$cmd = ($httpEmulation)?
    					$this->_CMD_PARAMETERS[$command]['http']['url']
    					: $command;
    		$cmd = str_replace(array_keys($parameters), array_values($parameters), $cmd);
    		$this->setCommand($cmd);
    		
    		$this->cmdIdx = array_search($command, array_keys($this->_CMD_PARAMETERS));
    		if ($this->cmdIdx === false) throw new \Exception('Command not supported');
    		
    		// creating equivalent commands
    		if ($httpEmulation && $createEquivalents)
	    		foreach($this->_CMD_PARAMETERS[$command]['http']['equivalents'] as $equivalent) {
	    			$equiv = new MyfoxCommand($equivalent, $parameters, $httpEmulation, false);
	    			$equiv->setOriginalCommand($this)->setState(self::STATE_WAITING_PARENT);
	    			$this->addEquivalent($equiv);
	    		}
    	}
    	if ($command && is_numeric($command)) {
    		//$macro = $this->_MACRO_PARAMETERS[$command];
    		$this->setCommand($command);
    		$this->cmdIdx = 0 - intval($command); // cmdIdx is opposite of macro nbr
    		$this->setMacroParameters(json_encode($parameters));
    		
    		// no equivalents for macro
    	}
    }

    
    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }


    /**
     * Set state
     *
     * @param integer $state
     * @return MyfoxCommand
     */
    public function setState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * Get state
     *
     * @return integer 
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Set command
     *
     * @param string $command
     * @return MyfoxCommand
     */
    public function setCommand($command)
    {
        $this->command = $command;

        return $this;
    }

    /**
     * Get command
     *
     * @return string 
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * Set result
     *
     * @param string $result
     * @return MyfoxCommand
     */
    public function setResult($result)
    {
    	$this->result = $result;
        return $this;
    }

    /**
     * Get result
     *
     * @return string 
     */
    public function getResult()
    {
        return $this->result;
    }
    
    
    /**
     * Set validUntil
     *
     * @param integer $validUntil
     * @return MyfoxCommand
     */
    public function setValidUntil($validUntil)
    {
        $this->validUntil = $validUntil;

        return $this;
    }

    /**
     * Get validUntil
     *
     * @return integer 
     */
    public function getValidUntil()
    {
        return $this->validUntil;
    }
    
    /**
     * Set cmdIdx
     *
     * @param integer $cmdIdx
     * @return MyfoxCommand
     */
    public function setCmdIdx($cmdIdx)
    {
    	$this->cmdIdx = $cmdIdx;
    
    	return $this;
    }
    
    /**
     * Get cmdIdx
     *
     * @return integer
     */
    public function getCmdIdx()
    {
    	return $this->cmdIdx;
    }
    
    
    /**
     * Set originalCommand
     *
     * @param \GXApplications\HomeAutomationBundle\Entity\MyfoxCommand $originalCommand
     * @return MyfoxCommand
     */
    public function setOriginalCommand(\GXApplications\HomeAutomationBundle\Entity\MyfoxCommand $originalCommand = null)
    {
    	$this->originalCommand = $originalCommand;
    
    	return $this;
    }
    
    /**
     * Get originalCommand
     *
     * @return \GXApplications\HomeAutomationBundle\Entity\MyfoxCommand
     */
    public function getOriginalCommand()
    {
    	return $this->originalCommand;
    }
    
    /**
     * Add equivalents
     *
     * @param \GXApplications\HomeAutomationBundle\Entity\MyfoxCommand $equivalents
     * @return MyfoxCommand
     */
    public function addEquivalent(\GXApplications\HomeAutomationBundle\Entity\MyfoxCommand $equivalents)
    {
    	$this->equivalents[] = $equivalents;
    
    	return $this;
    }
    
    /**
     * Remove equivalents
     *
     * @param \GXApplications\HomeAutomationBundle\Entity\MyfoxCommand $equivalents
     */
    public function removeEquivalent(\GXApplications\HomeAutomationBundle\Entity\MyfoxCommand $equivalents)
    {
    	$this->equivalents->removeElement($equivalents);
    }
    
    /**
     * Get equivalents
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEquivalents()
    {
    	return $this->equivalents;
    }
    
    
    
    
    

    
    /**
     * Compute and store its own validity timestamp.
     */
    public function calcValidity() {
    	$validities = array_values($this->_CMD_PARAMETERS);
    	$this->setValidUntil(time() + $validities[$this->cmdIdx]['validity']);
    }
    
    /**
     * Get a valid result for the same command, if still valid
     *
     * @param EntityManager $em
     * @return MyfoxCommand
     */
    public function getValidCache(EntityManager $em) {
    	$now = time();
    	$query = $em->getRepository('GXHomeAutomationBundle:MyfoxCommand')->createQueryBuilder('c')
			    	->where('c.validUntil >= :now')
			    	->andWhere('c.state = :state')
			    	->andWhere('c.cmdIdx = :cmdIdx')
			    	->setParameter('now', $now)
			    	->setParameter('cmdIdx', $this->getCmdIdx())
			    	->setParameter('state', self::STATE_SUCCESS)
			    	->orderBy('c.validUntil', 'DESC')
			    	->setMaxResults(1)
			    	->getQuery();
    	$results = $query->getResult();
    	return (sizeof($results)>0)?$results[0]:null;
    }
    
    /**
     * For Http Emulation mode only.
     * 
     * @return boolean True if Http query should be made in POST
     */
    public function isPost() {
    	return array_values($this->_CMD_PARAMETERS)[$this->cmdIdx]['http']['is_post'];
    }
    
    /**
     * Simple case of a HTTPEmulated parse method for POST requests.
     * 
     * @param unknown $raw
     * @throws \Exception
     * @return array
     */
    private static function parseSimplePost($raw) {
    	try {
	    	// {code: "OK"} ou {"code":"KO","msg":[["Une erreur est survenue. (650)","error"]]}
	    	// --> { "status": "OK", "timestamp": 1423147114, "payload": {} } ou { "status": "KO", "timestamp": 1423147114, "error": "..." }
	    	if (json_decode($raw, true)['code'] == "OK") return array();
	    	//else throw new \Exception(json_decode($raw, true)['msg'].""); // to string cast...
	    	else throw new \Exception("KO"); // to string cast...
    	} catch(\Exception $e) {
    		throw new \Exception("Unable to parse error returned by Myfox response.");
    	}
    }
    
    /**
     * @param string $rawResult
     * @param Symfony\Component\DependencyInjection\Container $container
     * @param EntityManager $em
     * @param boolean $httpEmulation
     */
    public function dispatchResult($rawResult, $container, EntityManager $em, $httpEmulation = false) {
    	if ($this->getState() >= self::STATE_SUCCESS) return;
    	
    	if (!is_numeric($this->getCommand())) { // CMD case, not MACRO
    		$parsedResult = $this->parseResult($rawResult, $container, $httpEmulation);
    		if ($parsedResult !== false) {
		    	$this->setResult($parsedResult);
		    	$this->setState(MyfoxCommand::STATE_SUCCESS);
	    		$this->calcValidity();
    		} else {
    			$this->setState(MyfoxCommand::STATE_FAIL);
    		}
	    	
	    	if ($httpEmulation)
		    	foreach($this->getEquivalents() as $equivalent) {
		    		$pResult = $equivalent->parseResult($rawResult, $container, $httpEmulation);
		    		/* @var $equivalent MyfoxCommand */
		    		if ($pResult !== false) {
			    		$equivalent->setResult($pResult);
			    		$equivalent->setState(self::STATE_SUCCESS);
			    		$equivalent->calcValidity();
		    		} else {
		    			$equivalent->setState(self::STATE_FAIL);
		    		}
		    	}
    	} else { // MACRO case
    		$this->setResult($rawResult)->setState(MyfoxCommand::STATE_SUCCESS);
	    	// MACRO has no cache validity!
    		// There is no equivalents for result dispatch.
    	}
    }
    
    /**
     * Parse result
     *
     * @param string $result
     * @param Symfony\Component\DependencyInjection\Container $container
     * @param boolean $httpEmulated
     * @return string
     */
    public function parseResult($rawResult, $container, $httpEmulated = false) {
    	if (is_numeric($this->getCommand())) return $rawResult; // For MACRO, do not parse.
    	
    	if ($httpEmulated) {
    		// general result format for API(JSON) case :
    		/* {
					"status": "OK",
					"timestamp": 1423147114,
					"payload": {}
				} */
    		$result = array(
    			"status" => "OK",
    			"timestamp" => time()
    		);
    		$httpParameters = array_values($this->_CMD_PARAMETERS)[$this->cmdIdx]['http'];
    		$parseFunction = $httpParameters['parser'];
    		try {
    			$result['payload'] = $parseFunction($rawResult, $container); // call predefined function.
    			if ($result['payload'] === false) {
    				$container->get('logger')->error('HttpEmulated response parsed but void.');
    				return false;
    			}
    			$container->get('logger')->info('HttpEmulated response parsed. Set payload value to: '.json_encode($result['payload']));
    		} catch (Exception $e) {
    			$container->get('logger')->error('HttpEmulated response parsing error.');
    			$result['status'] = "KO";
    			$result['error'] = $e->getMessage();
    		}
    		
    		return json_encode($result);
    	} else {
    		return $rawResult; // already in JSON format.
    	}
    }

	public function executeMacro($curlHandler, $em, $httpEmulation, $logger) {
		$commandIdx = intval($this->getCommand());
		$exe = $this->_MACRO_PARAMETERS[$commandIdx]['execute'];
		$params = $this->getMacroParameters();
		return $exe($params?json_decode($params, true):null, $curlHandler, $em, $httpEmulation, $logger);
	}


    /**
     * Set macro_parameters
     *
     * @param string $macroParameters
     * @return MyfoxCommand
     */
    public function setMacroParameters($macroParameters)
    {
        $this->macro_parameters = $macroParameters;

        return $this;
    }

    /**
     * Get macro_parameters
     *
     * @return string 
     */
    public function getMacroParameters()
    {
        return $this->macro_parameters;
    }
}
