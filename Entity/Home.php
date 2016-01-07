<?php

namespace GXApplications\HomeAutomationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use GXApplications\HomeAutomationBundle\Entity\Page;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Home
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class Home
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
     * @var ArrayCollection
     * 
     * @ORM\OneToMany(targetEntity="Page", mappedBy="home", cascade={"remove"}, orphanRemoval=true)
     */
    private $pages;
    
    /**
     * @var Page
     * 
     * @ORM\OneToOne(targetEntity="Page", mappedBy="default_page", cascade={"remove"}, orphanRemoval=true)
     * @ORM\JoinColumn(name="default_page", referencedColumnName="id", onDelete="CASCADE")
     */
    private $default_page;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="last_access", type="datetime")
     */
    private $last_access;
    
    /**
     * @var string
     *
     * @ORM\Column(name="home_key", type="string", length=16)
     */
    private $home_key;
    
    /**
     * @ORM\ManyToOne(targetEntity="Account", inversedBy="homes")
     * @ORM\JoinColumn(name="account_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $account;
    
    
    
    public function __construct()
    {
    	$this->pages = new ArrayCollection();
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
     * @return Home
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
     * Add pages
     *
     * @param \GXApplications\HomeAutomationBundle\Entity\Page $pages
     * @return Home
     */
    public function addPage(\GXApplications\HomeAutomationBundle\Entity\Page $pages)
    {
        $this->pages[] = $pages;

        return $this;
    }

    /**
     * Remove pages
     *
     * @param \GXApplications\HomeAutomationBundle\Entity\Page $pages
     */
    public function removePage(\GXApplications\HomeAutomationBundle\Entity\Page $pages)
    {
        $this->pages->removeElement($pages);
    }

    /**
     * Get pages
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getPages()
    {
        return $this->pages;
    }
    
    /**
     * Set default_page
     *
     * @param \GXApplications\HomeAutomationBundle\Entity\Page $defaultPage
     * @return Home
     */
    public function setDefaultPage(\GXApplications\HomeAutomationBundle\Entity\Page $defaultPage = null)
    {
        $this->default_page = $defaultPage;

        return $this;
    }

    /**
     * Get default_page
     *
     * @return \GXApplications\HomeAutomationBundle\Entity\Page 
     */
    public function getDefaultPage()
    {
        return $this->default_page;
    }

    /**
     * Set last_access
     *
     * @param \DateTime $lastAccess
     * @return Home
     */
    public function setLastAccess($lastAccess)
    {
        $this->last_access = $lastAccess;

        return $this;
    }

    /**
     * Get last_access
     *
     * @return \DateTime 
     */
    public function getLastAccess()
    {
        return $this->last_access;
    }

    /**
     * Set home_key
     *
     * @param string $home_key
     * @return Home
     */
    public function setHomeKey($home_key)
    {
        $this->home_key = $home_key;

        return $this;
    }

    /**
     * Get home_key
     *
     * @return string 
     */
    public function getHomeKey()
    {
        return $this->home_key;
    }

    /**
     * Set account
     *
     * @param \GXApplications\HomeAutomationBundle\Entity\Account $account
     * @return Home
     */
    public function setAccount(\GXApplications\HomeAutomationBundle\Entity\Account $account = null)
    {
        $this->account = $account;

        return $this;
    }

    /**
     * Get account
     *
     * @return \GXApplications\HomeAutomationBundle\Entity\Account 
     */
    public function getAccount()
    {
        return $this->account;
    }
}
