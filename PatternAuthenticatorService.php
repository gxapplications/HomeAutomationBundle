<?php
namespace GXApplications\HomeAutomationBundle;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\SimpleFormAuthenticatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use GXApplications\HomeAutomationBundle\Entity\Account;

class PatternAuthenticatorService implements SimpleFormAuthenticatorInterface
{
    private $encoder;
    private $service;
    
    private $router;

    public function __construct(UserPasswordEncoderInterface $encoder, MyfoxService $service)
    {
        $this->encoder = $encoder;
        $this->service = $service;
    }

    public function authenticateToken(TokenInterface $token, UserProviderInterface $userProvider, $providerKey)
    {
        try {
            $user = $userProvider->loadUserByUsername($token->getUsername());
        } catch (UsernameNotFoundException $e) {
            throw new AuthenticationException('Invalid account');
        }

        $passwordValid = $this->encoder->isPasswordValid($user, $token->getCredentials());

        if ($passwordValid) {
        	try {
        		if ($user instanceof Account) {
		        	$this->service->registerHome($user->getAccountLogin(), $user->getClearAccountPassword($token->getCredentials()));
        		}
        	} catch (\Exception $e) {
        		throw new AuthenticationException('Myfox account password invalid.');
        		//throw new AuthenticationException('Myfox account password invalid ('.$e->getMessage().')');
        	}
            return new UsernamePasswordToken(
                $user,
                $user->getPassword(),
                $providerKey,
                $user->getRoles()
            );
        }

        throw new AuthenticationException('Invalid pattern');
    }

    public function supportsToken(TokenInterface $token, $providerKey)
    {
        return $token instanceof UsernamePasswordToken
            && $token->getProviderKey() === $providerKey;
    }

    public function createToken(Request $request, $username, $password, $providerKey)
    {
        return new UsernamePasswordToken($username, $password, $providerKey);
    }

}