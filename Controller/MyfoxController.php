<?php

namespace GXApplications\HomeAutomationBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use GXApplications\HomeAutomationBundle\Entity\MyfoxCommand;
use GXApplications\HomeAutomationBundle\Entity\Component;
use GXApplications\HomeAutomationBundle\MyfoxService;

class MyfoxController extends Controller
{
	
	/**
	 * @Route("/myfox/getSync", name="_get_sync_myfox")
	 * @Method({"POST"})
	 */
	public function getSyncAction()
	{
		$homeKey = $this->getRequest()->get('home_key', null);
		if (!$homeKey) return new Response('Home Key not found', 500);
		 
		$command = $this->getRequest()->get('command', null);
		if (!$command) return new Response('Command not found', 404);
		$command = constant('GXApplications\HomeAutomationBundle\Entity\MyfoxCommand::'.$command);
		if (!$command) return new Response('Command unknown', 404);
		 
		$parameters = $this->getRequest()->get('parameters', array());
		 
		/* @var $myfox MyfoxService */
		$myfox = $this->get('gx_home_automation.myfox');
		 
		$result = $myfox->playSync($homeKey, $command, $parameters, true, true); // do look in cache (because we retrieve a state) and do store result in cache.
		
		return new Response($result);
	}
	
    /**
     * @Route("/myfox/setSync", name="_set_sync_myfox")
     * @Method({"POST"})
     */
    public function setSyncAction()
    {
    	$homeKey = $this->getRequest()->get('home_key', null);
    	if (!$homeKey) return new Response('Home Key not found', 500);
    	
    	$command = $this->getRequest()->get('command', null);
    	if (!$command) return new Response('Command not found', 404);
    	$command = constant('GXApplications\HomeAutomationBundle\Entity\MyfoxCommand::'.$command);
    	if (!$command) return new Response('Command unknown', 404);
    	
    	$parameters = $this->getRequest()->get('parameters', array());
    	$last_action = $this->getRequest()->get('last_action', time());
    	
    	/* @var $myfox MyfoxService */
    	$myfox = $this->get('gx_home_automation.myfox');
    	
    	$result = $myfox->playSync($homeKey, $command, $parameters, false, true); // do not look in cache (because we POST an action) and store result in cache.
    	
    	// If component to update
    	$componentUpdate = $this->getRequest()->get('component_update', null);
    	if ($componentUpdate) {
    		if (json_decode($result, true)['status'] == "OK") {
	    		$em = $this->getDoctrine()->getManager();
	    		/* @var $componentUpdate Component */
	    		$componentUpdate = $em->getRepository('GXHomeAutomationBundle:Component')->find($componentUpdate);
	    		if ($componentUpdate) {
	    			$componentUpdate->setLastAction($last_action);
	    			$em->persist($componentUpdate);
	    			$em->flush($componentUpdate);
	    		}
    		}
    	}
    	
    	return new Response($result);
    }
    
    /**
     * @Route("/myfox/setSchedule", name="_set_schedule_myfox")
     * @Method({"POST"})
     */
    public function setScheduleAction()
    {
    	$homeKey = $this->getRequest()->get('home_key', null);
    	if (!$homeKey) return new Response('Home Key not found', 500);
    	 
    	$command = $this->getRequest()->get('command', null);
    	if (!$command) return new Response('Command not found', 404);
    	$command = constant('GXApplications\HomeAutomationBundle\Entity\MyfoxCommand::'.$command);
    	if (!$command) return new Response('Command unknown', 404);
    	 
    	$parameters = $this->getRequest()->get('parameters', array());
    	$when = $this->getRequest()->get('when', time());
    	$last_action = $this->getRequest()->get('last_action', time());
    	 
    	/* @var $myfox MyfoxService */
    	$myfox = $this->get('gx_home_automation.myfox');
    	
    	$myfox->schedule($homeKey, $command, $parameters, $when, true);
    	
    	// If component to update
    	$componentUpdate = $this->getRequest()->get('component_update', null);
    	if ($componentUpdate) {
    		$em = $this->getDoctrine()->getManager();
    		/* @var $componentUpdate Component */
    		$componentUpdate = $em->getRepository('GXHomeAutomationBundle:Component')->find($componentUpdate);
    		if ($componentUpdate) {
    			$componentUpdate->setLastAction($last_action);
    			$em->persist($componentUpdate);
    			$em->flush($componentUpdate);
    		}
    	}
    	 
    	return new Response(json_encode(array('status'=>'OK', 'when'=>$when)));
    }
    
    /**
     * @Route("/myfox/setAlarmStatus", name="_set_alarm_status_myfox")
     * @Method({"POST"})
     */
    public function setAlarmStatusAction()
    {
    	$homeKey = $this->getRequest()->get('home_key', null);
    	if (!$homeKey) return new Response('Home Key not found', 500);

    	$level = $this->getRequest()->get('level', null);
    	if (!$level) return new Response('Alarm level not given', 500);
    	 
    	$pattern = $this->getRequest()->get('pattern', null);
    	
    	/* @var $myfox MyfoxService */
    	$myfox = $this->get('gx_home_automation.myfox');
    	 
    	$result = $myfox->setAlarmStatus($homeKey, $level, $pattern);
    	 
    	return new Response($result);
    }
    
    /**
     * @Route("/myfox/setMultipleStatesScenarioActivation", name="_set_multiple_states_scenario_activation")
     * @Method({"POST"})
     */
    public function setMultipleStatesScenarioActivationAction() {
    	$homeKey = $this->getRequest()->get('home_key', null);
    	if (!$homeKey) return new Response('Home Key not found', 500);
    	
    	$when = $this->getRequest()->get('when', time());
    	$last_action = $this->getRequest()->get('last_action', time());
    	$scenario_position = $this->getRequest()->get('scenario_position', 1) - 1; // translate to zero leading.
    	
    	$componentId = $this->getRequest()->get('component', null);
    	if (!$componentId) return new Response('Component Id not found', 500);
    	
    	$em = $this->getDoctrine()->getManager();
    	/* @var $component Component */
    	$component = $em->getRepository('GXHomeAutomationBundle:Component')->find($componentId);
    	if (!$component) return new Response('Component not found', 500);
    	
    	/* @var $myfox MyfoxService */
    	$myfox = $this->get('gx_home_automation.myfox');
    	 
    	$radioMode = $component->getOption1();
    	$scenarii = array($component->getForeignId1(), $component->getForeignId2(), $component->getForeignId3(), $component->getForeignId4());
    	$scenariiStates = array($component->getForeignId1() => null, $component->getForeignId2()?:"null" => null, $component->getForeignId3()?:"null" => null, $component->getForeignId4()?:"null" => null);
    	$scenario = $scenarii[$scenario_position];
    	if (!$scenario) return new Response("Scenario Id not found", 500);
    	
    	switch ($radioMode) {
    		
    		// Linked scenarii, radio button mode, at most 1 selected.
    		case 2: // switch state of clicked scenario, and deactivate others.
    			foreach($scenarii as $s) {
    				if ($s != $scenario && strlen($s)>0) {
    					$result = $this->setStateScenarioActivation($myfox, $component, $homeKey, $s, $when, false);
    					if ($result !== true) return $result; // Error during Sync set call.
    					$scenariiStates[$s] = false;
    				}
    			}
    			// no break: use case 0 to switch clicked scenario.
    			
    		// Independant scenarii.
    		case 0: // switch state of clicked scenario. Nothing more.
    			$currentStateQuery = $myfox->playSync($homeKey, MyfoxCommand::CMD_GET_SCENARIO_ITEMS, array(), true, true); // do look in cache (because we retrieve a state) and do store result in cache.
    			$currentStateQuery = json_decode($currentStateQuery, true);
    			$currentStateQuery = $currentStateQuery['payload']['items'];
    			foreach($currentStateQuery as $s) {
    				if ($s['scenarioId'] == $scenario) {
    					$result = $this->setStateScenarioActivation($myfox, $component, $homeKey, $scenario, $when, !$s['enabled']);
    					if ($result !== true) return $result; // Error during Sync set call.
    					$scenariiStates[$scenario] = !$s['enabled'];
    					break;
    				}
    			}
    			break;
    			
    		// Linked scenarii, radio button mode, exactly one selected.
    		case 1: // activate $scenario, and deactivate others.
    			foreach($scenarii as $s) {
    				if (strlen($s)>0) {
	    				$result = $this->setStateScenarioActivation($myfox, $component, $homeKey, $s, $when, ($s == $scenario));
	    				if ($result !== true) return $result; // Error during Sync set call.
	    				$scenariiStates[$s] = ($s == $scenario);
    				}
    			}
    			break;
    	}
    	
    	// Update component in DB.
    	$component->setLastAction($last_action);
    	$em->persist($component);
    	$em->flush($component);
    	
    	// Cache invalidation to force a refresh.
    	$myfox->invalidateCachedCommand($homeKey, MyfoxCommand::CMD_GET_SCENARIO_ITEMS, array());
    	
    	// Building response
    	return new Response(json_encode(array(
    			'status' => 'OK',
    			'timestamp' => time(),
    			'payload' => array('scenario_states' => $scenariiStates)
    	)));
    }
    
    private function setStateScenarioActivation($myfox, $component, $homeKey, $scenarioId, $when, $enable) {
    	if ($component->getDelay1() > 0) {
    		// async set call
    		$myfox->schedule($homeKey,
    				$enable? MyfoxCommand::CMD_SET_SCENARIO_ENABLE : MyfoxCommand::CMD_SET_SCENARIO_DISABLE,
    				array('%scenario_id%' => $scenarioId),
    				$when, true);
    	} else {
    		// sync set call
    		$result = $myfox->playSync($homeKey,
    				$enable? MyfoxCommand::CMD_SET_SCENARIO_ENABLE : MyfoxCommand::CMD_SET_SCENARIO_DISABLE,
    				array('%scenario_id%' => $scenarioId),
    				false, true); // do not look in cache (because we POST an action) and store result in cache.
    		if (json_decode($result, true)['status'] != "OK")
    			return new Response($result);
    	}
    	return true;
    }
    
}
