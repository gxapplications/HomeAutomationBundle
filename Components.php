<?php

namespace GXApplications\HomeAutomationBundle;

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

}
