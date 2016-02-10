<?php

namespace GXApplications\HomeAutomationBundle;

use GXApplications\HomeAutomationBundle\Entity\Component;
use GXApplications\HomeAutomationBundle\Entity\HeatingDashboardComponent;
use GXApplications\HomeAutomationBundle\Entity\Page;

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

	public static $constPreferredSizes = array(
		0 => ['w' => 1, 'h' => 1],
		1 => ['w' => 1, 'h' => 1],
		2 => ['w' => 1, 'h' => 1],
		3 => ['w' => 1, 'h' => 1],
		4 => ['w' => 1, 'h' => 1],
		5 => ['w' => 2, 'h' => 1],
		6 => ['w' => 2, 'h' => 2],
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

	public static function add($type, Page $page, $x, $y, $w, $h, $em) {
		$component = new Component();
		$component->setType($type);

		if ($type == 6) {
			$hdbc = new HeatingDashboardComponent();
			$hdbc->setComponent($component);
			$component->setHeatingDashboard($hdbc);
		}

		$em->persist($component);
		$em->flush();

		$positions = json_decode($page->getPositions(), true);
		$positions[] = [
			'id' => $component->getId(),
			'w' => $w, 'h' => $h, 'x' => $x, 'y' => $y
		];
		$page->setPositions(json_encode($positions));
		$em->persist($page);
		$em->flush();

		return $component->getId();
	}
}
