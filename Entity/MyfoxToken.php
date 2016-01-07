<?php

namespace GXApplications\HomeAutomationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * MyfoxToken
 *
 * @ORM\Table(options={"engine"="MEMORY"})
 * @ORM\Entity
 */
class MyfoxToken
{
	
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
     * @ORM\Column(name="account_login", type="string", length=256)
     */
    private $account_login;
    
    /**
     * @var string
     *
     * @ORM\Column(name="token", type="string", length=256, nullable=true)
     */
    private $token = null;
    
    /**
     * @var string
     *
     * @ORM\Column(name="refresh_token", type="string", length=256, nullable=true)
     */
    private $refresh_token = null;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="validity", type="integer")
     */
    private $validity;

    /**
     * @var integer
     *
     * @ORM\Column(name="account_password", type="string", length=256)
     */
    private $account_password;
    
    
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
     * Set account_login
     *
     * @param string $accountLogin
     * @return MyfoxToken
     */
    public function setAccountLogin($accountLogin)
    {
        $this->account_login = $accountLogin;

        return $this;
    }

    /**
     * Get account_login
     *
     * @return string 
     */
    public function getAccountLogin()
    {
        return $this->account_login;
    }

    /**
     * Set token
     *
     * @param string $token
     * @return MyfoxToken
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Get token
     *
     * @return string 
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Set refresh_token
     *
     * @param string $refreshToken
     * @return MyfoxToken
     */
    public function setRefreshToken($refreshToken)
    {
        $this->refresh_token = $refreshToken;

        return $this;
    }

    /**
     * Get refresh_token
     *
     * @return string 
     */
    public function getRefreshToken()
    {
        return $this->refresh_token;
    }

    /**
     * Set validity
     *
     * @param integer $validity
     * @return MyfoxToken
     */
    public function setValidity($validity)
    {
        $this->validity = $validity;

        return $this;
    }

    /**
     * Get validity
     *
     * @return integer 
     */
    public function getValidity()
    {
        return $this->validity;
    }

    /**
     * Set account_password
     *
     * @param string $accountPassword
     * @return MyfoxToken
     */
    public function setAccountPassword($accountPassword)
    {
        $this->account_password = $accountPassword;

        return $this;
    }

    /**
     * Get account_password
     *
     * @return string 
     */
    public function getAccountPassword()
    {
        return $this->account_password;
    }
}
