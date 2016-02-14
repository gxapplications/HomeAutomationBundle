<?php

namespace GXApplications\HomeAutomationBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

use GXApplications\HomeAutomationBundle\Entity\Page;
use GXApplications\HomeAutomationBundle\Entity\Container;
use GXApplications\HomeAutomationBundle\Entity\Component;
use GXApplications\HomeAutomationBundle\Layouts;
use GXApplications\HomeAutomationBundle\Components;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use GXApplications\HomeAutomationBundle\Entity\MyfoxCommand;

class ComponentController extends Controller
{
	
    /**
     * @Route("/addComponent/page/{page_id}", name="_component_add")
     * @Method({"POST"})
     * @ParamConverter ("page", class="GXHomeAutomationBundle:Page", options={"id" = "page_id"})
     */
    public function addComponentAction(Page $page, Request $request)
    {
		if ($page == null) {
			throw new \Exception("Adding component failed. No page found.");
		}

    	$positionXY = $request->request->get('position', false); // indexed  array {x, y}
		$dimensionsWH = $request->request->get('dimensions', ['w' => 1, 'h' => 1]); // indexed  array {w, h}
    	$type = $request->request->getInt('type', false);
    	if ($positionXY !== false && $type !== false) {
    		$em = $this->getDoctrine()->getManager();
    		$id = Components::add($type, $page, $positionXY['x'], $positionXY['y'], $dimensionsWH['w'], $dimensionsWH['h'], $em);
    		return new Response($id);
    	} else throw new \Exception("Adding component failed.");
    }
    
    
    /**
     * @Route("/removeComponent/page/{page_id}", name="_component_remove")
     * @Method({"POST"})
     * @ParamConverter ("page", class="GXHomeAutomationBundle:Page", options={"id" = "page_id"})
     */
    public function removeComponentAction(Page $page, Request $request)
    {
		if ($page == null) {
			throw new \Exception("Removing component failed. No page found.");
		}

		$matrix = $request->request->get('matrix', []);
    	$componentId = $request->request->getInt('component_id', $request->request->getInt('id', false));
    	if ($matrix !== false && $componentId) {
    		$em = $this->getDoctrine()->getManager();
    		$component = $em->getRepository('GXHomeAutomationBundle:Component')->find($componentId);
    		$em->remove($component);
			$page->setPositions(json_encode($matrix));
			$em->persist($page);
			$em->flush();
    		return new Response(1);
    	} else throw new \Exception("Removing component failed.");
    }

    
    /**
	 * TODO !2: rework all this
     * @Route("/commitComponent/component/{component_id}", name="_component_commit",  requirements={"component_id" = "\d*"})
     * @Method({"POST"})
     * @ParamConverter ("component", class="GXHomeAutomationBundle:Component", options={"id" = "component_id"})
     */
    public function commitComponentAction(Component $component)
    {
    	$em = $this->getDoctrine()->getManager();
    	
    	$componentData = $this->getRequest()->request->get("component");
    	
    	$component->setTitle($componentData['title'])->setDescription($componentData['description']);
    	// TODO !108: sauvegarder l'icone
    	
    	switch ($component->getType()) {
    		case 2: // scenario activation
    			$component->setTitle2(array_key_exists('title_2', $componentData)?$componentData['title_2']:'');
    			$component->setTitle3(array_key_exists('title_3', $componentData)?$componentData['title_3']:'');
    			$component->setTitle4(array_key_exists('title_4', $componentData)?$componentData['title_4']:'');
    			$component->setDescription2(array_key_exists('description_2', $componentData)?$componentData['description_2']:'');
    			$component->setDescription3(array_key_exists('description_3', $componentData)?$componentData['description_3']:'');
    			$component->setDescription4(array_key_exists('description_4', $componentData)?$componentData['description_4']:'');
    			// no break!
    		case 1: // scenario play
    			$component->setForeignId1($componentData['scenario_id']);
    			$component->setForeignId2(array_key_exists('scenario_id_2', $componentData)?$componentData['scenario_id_2']:'');
    			$component->setForeignId3(array_key_exists('scenario_id_3', $componentData)?$componentData['scenario_id_3']:'');
    			$component->setForeignId4(array_key_exists('scenario_id_4', $componentData)?$componentData['scenario_id_4']:'');
    			$component->setDelay1(array_key_exists('delay', $componentData)?$componentData['delay']:0);
    			$component->setDelay2(array_key_exists('delay2', $componentData)?$componentData['delay2']:0);
    			$component->setOption1(array_key_exists('option', $componentData)?$componentData['option']:0);
    			break;
    			
    		case 6: // heating dashboard On/Off
    			$heatingDashboard = $component->getHeatingDashboard();
    			if(array_key_exists('scenarii_minimal_temp', $componentData['heating_dashboard'])) {
	    			$minScenarii = array_diff(array_unique($componentData['heating_dashboard']['scenarii_minimal_temp']), array(null, false, ''));
	    			$heatingDashboard->setScenariiMinimalTemp(implode(',', $minScenarii));
    			}
    			if(array_key_exists('scenarii_maximal_temp', $componentData['heating_dashboard'])) {
	    			$maxScenarii = array_diff(array_unique($componentData['heating_dashboard']['scenarii_maximal_temp']), array(null, false, ''));
	    			$heatingDashboard->setScenariiMaximalTemp(implode(',', $maxScenarii));
    			}
    			
    			break;
    	}
    	
    	$em->persist($component);
    	$em->flush();
    	return new Response(1);
    }
    
    /**
     * @Route("/showComponent/{component_id}/{forceIntervals}", name="_component_show",  requirements={"component_id" = "\d*"})
     * @Method({"GET"})
     * @ParamConverter ("component", class="GXHomeAutomationBundle:Component", options={"id" = "component_id"})
     */
    public function showAction(Component $component, $forceIntervals = false)
    {
    	$template = Components::$constTemplates[$component->getType()];

		if ($forceIntervals !== false) {
			$forceIntervals = explode('-', $forceIntervals);
			$refreshInterval = array_shift($forceIntervals);
		} else {
			$refreshInterval = Components::$constRefreshIntervals[$component->getType()];
		}

    	$content = $this->renderView(
			$template,
			array(
				'component' => $component,
				'refreshInterval' => $refreshInterval,
				'forceIntervals' => ($forceIntervals !== false)? json_encode($forceIntervals) : 'false',
			)
    	);

		// TODO !4: surcharger le tpl par type de component (ComponentController, L.185)

		return new Response($content);
    }

	/**
	 * @Route("/showComponent/", name="_component_show_get")
	 * @Method({"GET","POST"})
	 */
	public function showGetAction(Request $request)
	{
		$componentId = $request->get('component_id', $request->get('id', null));
		if ($componentId == null) {
			return Response::create('Component not found', 404);
		}

		$em = $this->getDoctrine()->getManager();
		$component = $em->getRepository('GXHomeAutomationBundle:Component')->find($componentId);
		if (!$component) {
			return Response::create('Component not found', 404);
		}

		$forceIntervals = $request->get('force_intervals', $request->get('forceIntervals', false));

		return $this->showAction($component, $forceIntervals);
	}
    			
}			