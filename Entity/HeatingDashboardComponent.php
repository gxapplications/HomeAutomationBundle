<?php

namespace GXApplications\HomeAutomationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * HeatingDashboardComponent
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class HeatingDashboardComponent
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
     * @ORM\Column(name="minimal_temp", type="integer")
     */
    private $minimal_temp = 17;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="maximal_temp", type="integer")
     */
    private $maximal_temp = 20;
    
    /**
     * @var string
     * Scenarii (separated by coma ',') to modify with the minimal temperature when the double knob component changes de min value.
     *
     * @ORM\Column(name="scenarii_minimal_temp", type="string", length=512)
     */
    private $scenarii_minimal_temp = "";
    
    /**
     * @var string
     * Scenarii (separated by coma ',') to modify with the maximal temperature when the double knob component changes de max value.
     *
     * @ORM\Column(name="scenarii_maximal_temp", type="string", length=512)
     */
    private $scenarii_maximal_temp = "";
    
    /**
     * @var string
     * Scenarii (separated by coma ',') to activate when the planer is in low economic level, and to deactivate for high confort level.
     *
     * @ORM\Column(name="scenarii_low_level", type="string", length=512)
     */
    private $scenarii_low_level = "";
    
    /**
     * @var string
     * Scenarii (separated by coma ',') to activate when the planer is in high confort level, and to deactivate for low economic level.
     *
     * @ORM\Column(name="scenarii_high_level", type="string", length=512)
     */
    private $scenarii_high_level = "";

    /**
     * @ORM\OneToOne(targetEntity="Component", inversedBy="heating_dashboard")
     * @ORM\JoinColumn(name="component_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    private $component;
    
    

    public function to_json() {
    	return json_encode($this->to_array());
    }
    
    public function to_array() {
    	$t = get_object_vars($this);
    	unset($t['component']);
    	$t['scenarii_minimal_temp'] = explode(',', $t['scenarii_minimal_temp']);
    	$t['scenarii_maximal_temp'] = explode(',', $t['scenarii_maximal_temp']);
    	$t['scenarii_low_level'] = explode(',', $t['scenarii_low_level']);
    	$t['scenarii_high_level'] = explode(',', $t['scenarii_high_level']);
    	return $t;
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
     * Set minimal_temp
     *
     * @param integer $minimalTemp
     * @return HeatingDashboardComponent
     */
    public function setMinimalTemp($minimalTemp)
    {
        $this->minimal_temp = $minimalTemp;

        return $this;
    }

    /**
     * Get minimal_temp
     *
     * @return integer 
     */
    public function getMinimalTemp()
    {
        return $this->minimal_temp;
    }

    /**
     * Set maximal_temp
     *
     * @param integer $maximalTemp
     * @return HeatingDashboardComponent
     */
    public function setMaximalTemp($maximalTemp)
    {
        $this->maximal_temp = $maximalTemp;

        return $this;
    }

    /**
     * Get maximal_temp
     *
     * @return integer 
     */
    public function getMaximalTemp()
    {
        return $this->maximal_temp;
    }

    /**
     * Set scenarii_minimal_temp
     *
     * @param string $scenariiMinimalTemp
     * @return HeatingDashboardComponent
     */
    public function setScenariiMinimalTemp($scenariiMinimalTemp)
    {
        $this->scenarii_minimal_temp = $scenariiMinimalTemp;

        return $this;
    }

    /**
     * Get scenarii_minimal_temp
     *
     * @return string 
     */
    public function getScenariiMinimalTemp()
    {
        return $this->scenarii_minimal_temp;
    }

    /**
     * Set scenarii_maximal_temp
     *
     * @param string $scenariiMaximalTemp
     * @return HeatingDashboardComponent
     */
    public function setScenariiMaximalTemp($scenariiMaximalTemp)
    {
        $this->scenarii_maximal_temp = $scenariiMaximalTemp;

        return $this;
    }

    /**
     * Get scenarii_maximal_temp
     *
     * @return string 
     */
    public function getScenariiMaximalTemp()
    {
        return $this->scenarii_maximal_temp;
    }

    /**
     * Set scenarii_low_level
     *
     * @param string $scenariiLowLevel
     * @return HeatingDashboardComponent
     */
    public function setScenariiLowLevel($scenariiLowLevel)
    {
        $this->scenarii_low_level = $scenariiLowLevel;

        return $this;
    }

    /**
     * Get scenarii_low_level
     *
     * @return string 
     */
    public function getScenariiLowLevel()
    {
        return $this->scenarii_low_level;
    }

    /**
     * Set scenarii_high_level
     *
     * @param string $scenariiHighLevel
     * @return HeatingDashboardComponent
     */
    public function setScenariiHighLevel($scenariiHighLevel)
    {
        $this->scenarii_high_level = $scenariiHighLevel;

        return $this;
    }

    /**
     * Get scenarii_high_level
     *
     * @return string 
     */
    public function getScenariiHighLevel()
    {
        return $this->scenarii_high_level;
    }

    /**
     * Set component
     *
     * @param \GXApplications\HomeAutomationBundle\Entity\Component $component
     * @return HeatingDashboardComponent
     */
    public function setComponent(\GXApplications\HomeAutomationBundle\Entity\Component $component = null)
    {
        $this->component = $component;

        return $this;
    }

    /**
     * Get component
     *
     * @return \GXApplications\HomeAutomationBundle\Entity\Component 
     */
    public function getComponent()
    {
        return $this->component;
    }
}
