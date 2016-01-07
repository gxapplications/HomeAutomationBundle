<?php

namespace GXApplications\HomeAutomationBundle;

use GXApplications\HomeAutomationBundle\Entity\Container;
use Doctrine\ORM\EntityManager;
use GXApplications\HomeAutomationBundle\Entity\Component;
use GXApplications\HomeAutomationBundle\Entity\HeatingDashboardComponent;

class Components
{
	
	public static $constNames = array(
			0 => 'Macro - combine actions', // possibilité d'enchainer des taches (scenarii, commande interr ou chauffage, etc...) avec des timers, des waits, etc...
			1 => 'Scenario - Play button', // Play + do it after delay X seconds + button state memorized for Y seconds
			2 => 'Scenario - Activation', // Activate/deactivate + do it after delay X seconds + action inverse after Y seconds + config pour l'état actif
			3 => 'Domotic - On/Off buttons', // contacteurs secs, variateurs, etc... param pour l'état actif (dernier appuyé, date péremption info)
			4 => 'Heating - Mode buttons', // chauffages (modes fil pilotes et chaudiere ?)
			5 => 'Protection - alarm buttons',
			6 => 'Heating - On/Off contactor dashboard', // Pour 1 chauffage piloté par un contacteur sec On/Off, contrôle complet asservi par la t°
			//7 => 'Heating - 4 orders piloted dashboard', // Pour 1 chauffage piloté par un fil pilote 4 ordres, contrôle complet asservi par la t°
	);
	
	public static $constTemplates = array(
			0 => 'GXHomeAutomationBundle:Component:component_macro.html.twig',
			1 => 'GXHomeAutomationBundle:Component:component_scenario_play.html.twig',
			2 => 'GXHomeAutomationBundle:Component:component_scenario_activation.html.twig',
			3 => 'GXHomeAutomationBundle:Component:component_macro.html.twig',
			4 => 'GXHomeAutomationBundle:Component:component_heating_modes.html.twig',
			5 => 'GXHomeAutomationBundle:Component:component_macro.html.twig',
			6 => 'GXHomeAutomationBundle:Component:component_heating_on_off_dashboard.html.twig',
	);
	
	public static $constEditTemplates = array(
			0 => 'GXHomeAutomationBundle:Component:component_edit_macro.html.twig',
			1 => 'GXHomeAutomationBundle:Component:component_edit_scenario_play.html.twig',
			2 => 'GXHomeAutomationBundle:Component:component_edit_scenario_activation.html.twig',
			3 => 'GXHomeAutomationBundle:Component:component_edit_macro.html.twig',
			4 => 'GXHomeAutomationBundle:Component:component_edit_heating_modes.html.twig',
			5 => 'GXHomeAutomationBundle:Component:component_edit_macro.html.twig',
			6 => 'GXHomeAutomationBundle:Component:component_edit_heating_on_off_dashboard.html.twig',
	);
	
	public static function enum() {
        $reflect = new \ReflectionClass( get_called_class() );
        return $reflect->getConstants();
    }
    
    /**
     * @param Container $container
     * @param int $type
     * @param EntityManager $em
     * 
     * @return int The new component ID
     */
    public static function add(Container $container, $type, EntityManager $em) {
    	// computing position in container 
    	$position = 0;
    	$lastComponents = $em->getRepository('GXHomeAutomationBundle:Component')->findBy(
    			array('container'=>$container->getId()),
    			array('container_position'=>'DESC'), 1);
    	if(sizeof($lastComponents)>0) {
    		$lastComponent = $lastComponents[0];
    		$position = $lastComponent->getContainerPosition() + 1;
    	}
    	
    	$component = new Component();
    	$component->setContainer($container)->setType($type)->setContainerPosition($position);
    	$container->addComponent($component);
    	
    	if ($type == 6) {
    		$hdbc = new HeatingDashboardComponent();
    		$hdbc->setComponent($component);
    		$component->setHeatingDashboard($hdbc);
    	}
    	
    	$em->persist($component);
    	$em->flush();
    				
    	return $component->getId();
    }
    
    /**
     * @param Container $container
     * @param Array $components
     * @param EntityManager $em
     *
     * @return boolean True for success
     */
    public static function sort(Container $container, $components, EntityManager $em) {
    	$offset = 0;
    	foreach($components as $position => $component) {
    		if (!$component) {
    			$offset--;
    			continue;
    		}
    		$component->setContainer($container)->setContainerPosition($position + $offset);
    		$em->persist($component);
    	}
    	$em->flush();
    	return true;
    }
    
    /**
     * @param Component $component
     * @param Container $container
     * @param EntityManager $em
     *
     * @return boolean True for success
     */
    public static function moveToTrail(Component $component, Container $container, EntityManager $em) {
    	$oldContainer = $component->getContainer();
    	$index = sizeof($container->getComponents());
    	$component->setContainer($container)->setContainerPosition($index);
    	$em->persist($component);
    	$em->flush();
    	
    	$em->refresh($oldContainer);
    	self::sort($oldContainer, $oldContainer->getComponents(), $em);
    	
    	$em->flush();
    	return true;
    }
    
}
