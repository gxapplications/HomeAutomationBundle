<?php

namespace GXApplications\HomeAutomationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use GXApplications\HomeAutomationBundle\Entity\Container;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Page
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class Page
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
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity="Home", inversedBy="pages")
     * @ORM\JoinColumn(name="home_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $home;
    
    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Container", mappedBy="page", cascade={"all"}, orphanRemoval=true)
     * @ORM\OrderBy({"layout_position" = "ASC"})
     */
    private $containers;

    /**
     * @var integer
     *
     * @ORM\Column(name="layout", type="integer")
     */
    private $layout;
    
    
    
    public function __construct()
    {
    	$this->containers = new ArrayCollection();
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
     * @return Page
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
     * Set home
     *
     * @param \GXApplications\HomeAutomationBundle\Entity\Home $home
     * @return Page
     */
    public function setHome(\GXApplications\HomeAutomationBundle\Entity\Home $home = null)
    {
        $this->home = $home;

        return $this;
    }

    /**
     * Get home
     *
     * @return \GXApplications\HomeAutomationBundle\Entity\Home 
     */
    public function getHome()
    {
        return $this->home;
    }
    
    /**
     * Add containers
     *
     * @param \GXApplications\HomeAutomationBundle\Entity\Container $containers
     * @return Page
     */
    public function addContainer(\GXApplications\HomeAutomationBundle\Entity\Container $containers)
    {
    	$this->containers[] = $containers;
    
    	return $this;
    }
    
    /**
     * Remove containers
     *
     * @param \GXApplications\HomeAutomationBundle\Entity\Container $containers
     */
    public function removeContainer(\GXApplications\HomeAutomationBundle\Entity\Container $containers)
    {
    	$this->containers->removeElement($containers);
    }
    
    /**
     * Get containers
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getContainers()
    {
    	return $this->containers;
    }

    /**
     * Set layout
     *
     * @param integer $layout
     * @return Page
     */
    public function setLayout($layout)
    {
        $this->layout = $layout;

        return $this;
    }

    /**
     * Get layout
     *
     * @return integer 
     */
    public function getLayout()
    {
        return $this->layout;
    }
}
