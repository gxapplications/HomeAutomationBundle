<?php

namespace GXApplications\HomeAutomationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use GXApplications\HomeAutomationBundle\Entity\Home;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Account
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class Account implements UserInterface
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
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=128)
     */
    private $name;
    
    /**
     * @var string
     *
     * @ORM\Column(name="pattern", type="string", length=512)
     */
    private $pattern;
    
    /**
     * @var string
     *
     * @ORM\Column(name="account_login", type="string", length=256)
     */
    private $account_login;

    /**
     * @var string
     *
     * @ORM\Column(name="account_password", type="string", length=512)
     */
    private $account_password;
    
    /**
     * @var ArrayCollection
     * 
     * @ORM\OneToMany(targetEntity="Home", mappedBy="account", cascade={"all"}, orphanRemoval=true)
     */
    private $homes;
    
    
    
    public function __construct()
    {
    	$this->homes = new ArrayCollection();
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
     * Set name
     *
     * @param string $name
     * @return Account
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }


    /**
     * Set account_login
     *
     * @param string $account_login
     * @return Account
     */
    public function setAccountLogin($account_login)
    {
        $this->account_login = $account_login;

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
     * Set account_password
     *
     * @param string $account_password
     * @return Account
     */
    public function setAccountPassword($account_password)
    {
        $this->account_password = $account_password;

        return $this;
    }

    /**
     * Returns the account_password used to authenticate the user.
     *
     * This should be the encoded password. On authentication, a plain-text
     * password will be salted, encoded, and then compared to this value.
     *
     * @return string The account_password
    */
    public function getAccountPassword()
    {
        return $this->account_password;
    }

    /**
     * Add homes
     *
     * @param \GXApplications\HomeAutomationBundle\Entity\Home $homes
     * @return Account
     */
    public function addHome(\GXApplications\HomeAutomationBundle\Entity\Home $homes)
    {
        $this->homes[] = $homes;

        return $this;
    }

    /**
     * Remove homes
     *
     * @param \GXApplications\HomeAutomationBundle\Entity\Home $homes
     */
    public function removeHome(\GXApplications\HomeAutomationBundle\Entity\Home $homes)
    {
        $this->homes->removeElement($homes);
    }

    /**
     * Get homes
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getHomes()
    {
        return $this->homes;
    }
    
    private $iv = '13245768';
    
    /**
     * Get account_password in clear version (decrypted by a clear $pattern)
     *
     * @param string $pattern
     * @return string
     */
    public function getClearAccountPassword($pattern) {
    	$cipher = mcrypt_module_open(MCRYPT_BLOWFISH,'','cbc','');
    	
    	mcrypt_generic_init($cipher, $pattern, $this->iv);
    	$decrypted = mdecrypt_generic($cipher,base64_decode($this->getAccountPassword()));
    	mcrypt_generic_deinit($cipher);
    	
    	return $decrypted;
    }
    
    /**
     * Set account_password from clear version (encrypted with a clear $pattern)
     *
     * @param string $account_password
     * @param string $pattern
     * @return Account
     */
    public function setClearAccountPassword($account_password, $pattern) {
    	$cipher = mcrypt_module_open(MCRYPT_BLOWFISH,'','cbc','');
    	
    	mcrypt_generic_init($cipher, $pattern, $this->iv);
    	$encrypted = mcrypt_generic($cipher,$account_password);
    	mcrypt_generic_deinit($cipher);
    	
    	$this->setAccountPassword(base64_encode($encrypted));
    	
    	return $this;
    }
    
    /**
     * Set pattern
     *
     * @param string $pattern
     * @return Account
     */
    public function setPattern($pattern)
    {
    	$this->pattern = $pattern;
    
    	return $this;
    }
    
    /**
     * Get pattern
     *
     * @return string
     */
    public function getPattern()
    {
    	return $this->pattern;
    }
    
    /**
     * Set pattern from clear version (hashed with salt and SF2 crypter)
     *
     * @param string $pattern
     * @return Account
     */
    public function setClearPattern($pattern) {
    	$this->setPattern($pattern);
    
    	return $this;
    }
    
    
    
///////////////////
// UserInterface //
///////////////////    
    
    /**
     * Returns the roles granted to the user.
     * Alternatively, the roles might be stored on a ``roles`` property,
     * and populated in any number of different ways when the user object
     * is created.
     *
     * @return Role[] The user roles
     */
    public function getRoles() {
    	return array('ROLE_USER', 'ROLE_ACCOUNT_'.$this->id);
    }
    
    /**
     * Returns the salt that was originally used to encode the password.
     *
     * This can return null if the password was not encoded using a salt.
     *
     * @return string|null The salt
    */
    public function getSalt() {
    	return "MON SEL A MOA";
    }
    
    /**
     * Returns the username used to authenticate the user.
     *
     * @return string The username
    */
    public function getUsername() {
    	return $this->getId();
    }
    
    /**
     * Removes sensitive data from the user.
     *
     * This is important if, at any given point, sensitive information like
     * the plain-text password is stored on this object.
    */
    public function eraseCredentials() {
    	
    }
    
	/* (non-PHPdoc)
	 * @see \Symfony\Component\Security\Core\User\UserInterface::getPassword()
	 */
	public function getPassword() {
		return $this->getPattern();
	}



}
