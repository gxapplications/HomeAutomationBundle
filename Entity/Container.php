<?php

namespace GXApplications\HomeAutomationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use GXApplications\HomeAutomationBundle\Entity\Component;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Container
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class Container
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
     * @ORM\Column(name="layout_position", type="integer")
     */
    private $layout_position;

    /**
     * @var integer
     *
     * @ORM\Column(name="border", type="integer")
     */
    private $border;
    
    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=128, nullable=true)
     */
    private $title;
    
    /**
     * @ORM\ManyToOne(targetEntity="Page", inversedBy="containers")
     * @ORM\JoinColumn(name="page_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $page;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Component", mappedBy="container", cascade={"all"}, orphanRemoval=true)
     * @ORM\OrderBy({"container_position" = "ASC"})
     */
    private $components;
    
    public function __construct()
    {
    	$this->components = new ArrayCollection();
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
     * Set layout_position
     *
     * @param integer $layoutPosition
     * @return Container
     */
    public function setLayoutPosition($layoutPosition)
    {
        $this->layout_position = $layoutPosition;

        return $this;
    }

    /**
     * Get layout_position
     *
     * @return integer 
     */
    public function getLayoutPosition()
    {
        return $this->layout_position;
    }

    /**
     * Set page
     *
     * @param \GXApplications\HomeAutomationBundle\Entity\Page $page
     * @return Container
     */
    public function setPage(\GXApplications\HomeAutomationBundle\Entity\Page $page = null)
    {
        $this->page = $page;

        return $this;
    }

    /**
     * Get page
     *
     * @return \GXApplications\HomeAutomationBundle\Entity\Page 
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * Set border
     *
     * @param integer $border
     * @return Container
     */
    public function setBorder($border)
    {
        $this->border = $border;

        return $this;
    }

    /**
     * Get border
     *
     * @return integer 
     */
    public function getBorder()
    {
        return $this->border;
    }

    /**
     * Add components
     *
     * @param \GXApplications\HomeAutomationBundle\Entity\Component $components
     * @return Container
     */
    public function addComponent(\GXApplications\HomeAutomationBundle\Entity\Component $components)
    {
        $this->components[] = $components;

        return $this;
    }

    /**
     * Remove components
     *
     * @param \GXApplications\HomeAutomationBundle\Entity\Component $components
     */
    public function removeComponent(\GXApplications\HomeAutomationBundle\Entity\Component $components)
    {
        $this->components->removeElement($components);
    }

    /**
     * Get components
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getComponents()
    {
        return $this->components;
    }
    
    
    public function to_json() {
    	$t1 = get_object_vars($this);
    	$components = array();
    	foreach ($t1['components'] as $c) {
    		$t2 = $c->to_array();
    		$components[] = $t2;
    	}
    	$t1['components'] = $components;
    	return json_encode($t1);
    }
    
    

    /**
     * Set title
     *
     * @param string $title
     * @return Container
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle()
    {
        return $this->title;
    }
}
