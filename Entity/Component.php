<?php

namespace GXApplications\HomeAutomationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Component
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class Component
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
     * @ORM\Column(name="container_position", type="integer")
     */
    private $container_position;

    /**
     * @var integer
     *
     * @ORM\Column(name="type", type="integer")
     */
    private $type;
    
    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=128, nullable=true)
     */
    private $title = null;
    
    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=512, nullable=true)
     */
    private $description = null;
    
    /**
     * @var string
     *
     * @ORM\Column(name="foreign_id_1", type="string", length=128, nullable=true)
     */
    private $foreign_id_1 = null;
    
    /**
     * @var string
     *
     * @ORM\Column(name="foreign_id_2", type="string", length=128, nullable=true)
     */
    private $foreign_id_2 = null;
    
    /**
     * @var string
     *
     * @ORM\Column(name="foreign_id_3", type="string", length=128, nullable=true)
     */
    private $foreign_id_3 = null;
    
    /**
     * @var string
     *
     * @ORM\Column(name="foreign_id_4", type="string", length=128, nullable=true)
     */
    private $foreign_id_4 = null;
    
    /**
     * @var string
     *
     * @ORM\Column(name="title_2", type="string", length=128, nullable=true)
     */
    private $title_2 = null;
    
    /**
     * @var string
     *
     * @ORM\Column(name="description_2", type="string", length=512, nullable=true)
     */
    private $description_2 = null;
    
    /**
     * @var string
     *
     * @ORM\Column(name="title_3", type="string", length=128, nullable=true)
     */
    private $title_3 = null;
    
    /**
     * @var string
     *
     * @ORM\Column(name="description_3", type="string", length=512, nullable=true)
     */
    private $description_3 = null;
    
    /**
     * @var string
     *
     * @ORM\Column(name="title_4", type="string", length=128, nullable=true)
     */
    private $title_4 = null;
    
    /**
     * @var string
     *
     * @ORM\Column(name="description_4", type="string", length=512, nullable=true)
     */
    private $description_4 = null;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="delay_1", type="integer", nullable=false, options={"unsigned"=true, "default"=0})
     */
    private $delay_1 = 0;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="delay_2", type="integer", nullable=false, options={"unsigned"=true, "default"=0})
     */
    private $delay_2 = 0;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="option_1", type="smallint", nullable=true, options={"unsigned"=true, "default"=0})
     */
    private $option_1 = 0;
    
    /**
     * @ORM\ManyToOne(targetEntity="Container", inversedBy="components")
     * @ORM\JoinColumn(name="container_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $container;

    /**
     * @var integer
     *
     * @ORM\Column(name="last_action", type="integer", nullable=true)
     */
    private $last_action;
    
    /**
     * @var ArrayCollection
     *
     * @ORM\OneToOne(targetEntity="HeatingDashboardComponent", mappedBy="component", cascade={"all"}, orphanRemoval=true)
     */
    private $heating_dashboard;
    
    
    
    /**
     * @var integer
     * Not an ORM column! For Angular model use.
     */
    public $state = 0;
    /**
     * @var integer
     */
    public $state_2 = 0;
    /**
     * @var integer
     */
    public $state_3 = 0;
    /**
     * @var integer
     */
    public $state_4 = 0;
    
    
    
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
     * Set container_position
     *
     * @param integer $containerPosition
     * @return Component
     */
    public function setContainerPosition($containerPosition)
    {
        $this->container_position = $containerPosition;

        return $this;
    }

    /**
     * Get container_position
     *
     * @return integer 
     */
    public function getContainerPosition()
    {
        return $this->container_position;
    }

    /**
     * Set type
     *
     * @param integer $type
     * @return Component
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return integer 
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set container
     *
     * @param \GXApplications\HomeAutomationBundle\Entity\Container $container
     * @return Component
     */
    public function setContainer(\GXApplications\HomeAutomationBundle\Entity\Container $container = null)
    {
        $this->container = $container;

        return $this;
    }

    /**
     * Get container
     *
     * @return \GXApplications\HomeAutomationBundle\Entity\Container 
     */
    public function getContainer()
    {
        return $this->container;
    }
    
    
    public function to_json() {
    	return json_encode($this->to_array());
    }
    
    public function to_array() {
    	$t = get_object_vars($this);
    	if ($this->type == 6) { // Add HeatingDashboardComponent to the dump
    		$hdbc = $this->getHeatingDashboard();
    		if ($hdbc) {
    			$t['heating_dashboard'] = $hdbc->to_array();
    		}
    	} else unset($t['heating_dashboard']);
    	return $t;
    }
    

    /**
     * Set title
     *
     * @param string $title
     * @return Component
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

    /**
     * Set description
     *
     * @param string $description
     * @return Component
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set foreign_id_1
     *
     * @param string $foreignId1
     * @return Component
     */
    public function setForeignId1($foreignId1)
    {
        $this->foreign_id_1 = $foreignId1;

        return $this;
    }

    /**
     * Get foreign_id_1
     *
     * @return string 
     */
    public function getForeignId1()
    {
        return $this->foreign_id_1;
    }

    /**
     * Set foreign_id_2
     *
     * @param string $foreignId2
     * @return Component
     */
    public function setForeignId2($foreignId2)
    {
        $this->foreign_id_2 = $foreignId2;

        return $this;
    }

    /**
     * Get foreign_id_2
     *
     * @return string 
     */
    public function getForeignId2()
    {
        return $this->foreign_id_2;
    }

    /**
     * Set delay_1
     *
     * @param integer $delay_1
     * @return Component
     */
    public function setDelay1($delay_1)
    {
        $this->delay_1 = $delay_1;

        return $this;
    }

    /**
     * Get delay_1
     *
     * @return integer 
     */
    public function getDelay1()
    {
        return $this->delay_1;
    }
    
    /**
     * Set delay_2
     *
     * @param integer $delay_2
     * @return Component
     */
    public function setDelay2($delay_2)
    {
    	$this->delay_2 = $delay_2;
    
    	return $this;
    }
    
    /**
     * Get delay_2
     *
     * @return integer
     */
    public function getDelay2()
    {
    	return $this->delay_2;
    }

    /**
     * Set last_action
     *
     * @param integer $lastAction
     * @return Component
     */
    public function setLastAction($lastAction)
    {
        $this->last_action = $lastAction;

        return $this;
    }

    /**
     * Get last_action
     *
     * @return integer 
     */
    public function getLastAction()
    {
        return $this->last_action;
    }

    /**
     * Set option_1
     *
     * @param integer $option1
     * @return Component
     */
    public function setOption1($option1)
    {
        $this->option_1 = $option1;

        return $this;
    }

    /**
     * Get option_1
     *
     * @return integer 
     */
    public function getOption1()
    {
        return $this->option_1;
    }

    /**
     * Set foreign_id_3
     *
     * @param string $foreignId3
     * @return Component
     */
    public function setForeignId3($foreignId3)
    {
        $this->foreign_id_3 = $foreignId3;

        return $this;
    }

    /**
     * Get foreign_id_3
     *
     * @return string 
     */
    public function getForeignId3()
    {
        return $this->foreign_id_3;
    }

    /**
     * Set foreign_id_4
     *
     * @param string $foreignId4
     * @return Component
     */
    public function setForeignId4($foreignId4)
    {
        $this->foreign_id_4 = $foreignId4;

        return $this;
    }

    /**
     * Get foreign_id_4
     *
     * @return string 
     */
    public function getForeignId4()
    {
        return $this->foreign_id_4;
    }


    /**
     * Set title_2
     *
     * @param string $title2
     * @return Component
     */
    public function setTitle2($title2)
    {
        $this->title_2 = $title2;

        return $this;
    }

    /**
     * Get title_2
     *
     * @return string 
     */
    public function getTitle2()
    {
        return $this->title_2;
    }

    /**
     * Set description_2
     *
     * @param string $description2
     * @return Component
     */
    public function setDescription2($description2)
    {
        $this->description_2 = $description2;

        return $this;
    }

    /**
     * Get description_2
     *
     * @return string 
     */
    public function getDescription2()
    {
        return $this->description_2;
    }

    /**
     * Set title_3
     *
     * @param string $title3
     * @return Component
     */
    public function setTitle3($title3)
    {
        $this->title_3 = $title3;

        return $this;
    }

    /**
     * Get title_3
     *
     * @return string 
     */
    public function getTitle3()
    {
        return $this->title_3;
    }

    /**
     * Set description_3
     *
     * @param string $description3
     * @return Component
     */
    public function setDescription3($description3)
    {
        $this->description_3 = $description3;

        return $this;
    }

    /**
     * Get description_3
     *
     * @return string 
     */
    public function getDescription3()
    {
        return $this->description_3;
    }

    /**
     * Set title_4
     *
     * @param string $title4
     * @return Component
     */
    public function setTitle4($title4)
    {
        $this->title_4 = $title4;

        return $this;
    }

    /**
     * Get title_4
     *
     * @return string 
     */
    public function getTitle4()
    {
        return $this->title_4;
    }

    /**
     * Set description_4
     *
     * @param string $description4
     * @return Component
     */
    public function setDescription4($description4)
    {
        $this->description_4 = $description4;

        return $this;
    }

    /**
     * Get description_4
     *
     * @return string 
     */
    public function getDescription4()
    {
        return $this->description_4;
    }

    /**
     * Set heating_dashboard
     *
     * @param \GXApplications\HomeAutomationBundle\Entity\HeatingDashboardComponent $heatingDashboard
     * @return Component
     */
    public function setHeatingDashboard(\GXApplications\HomeAutomationBundle\Entity\HeatingDashboardComponent $heatingDashboard = null)
    {
        $this->heating_dashboard = $heatingDashboard;

        return $this;
    }

    /**
     * Get heating_dashboard
     *
     * @return \GXApplications\HomeAutomationBundle\Entity\HeatingDashboardComponent 
     */
    public function getHeatingDashboard()
    {
        return $this->heating_dashboard;
    }
}
