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

    	$positionXY = $request->request->get('position', false);// indexed  array {x, y}
    	$type = $request->request->getInt('type', false);
    	if ($positionXY !== false && $type !== false) {
    		$em = $this->getDoctrine()->getManager();
    		$id = Components::add($type, $page, $positionXY['x'], $positionXY['y'], $em);
    		return new Response($id);
    	} else throw new \Exception("Adding component failed.");
    }
    
    
    /**
	 * FIXME !10: rework all this
     * @Route("/removeComponent/page/{page_id}", name="_component_remove")
     * @Method({"POST"})
     * @ParamConverter ("page", class="GXHomeAutomationBundle:Page", options={"id" = "page_id"})
     */
    public function removeComponentAction(Page $page)
    {
    	$componentId = $this->getRequest()->request->getInt('component_id');
    	if ($componentId) {
    		$em = $this->getDoctrine()->getManager();
    		$component = $em->getRepository('GXHomeAutomationBundle:Component')->find($componentId);
    		$em->remove($component);
    		$em->flush();
    		return new Response(1);
    	} else throw new \Exception("Removing component failed.");
    }
    
    
    /**
	 * FIXME !10: rework all this
     * @Route("/sortComponent/page/{page_id}", name="_component_sort")
     * @Method({"POST"})
     * @ParamConverter ("page", class="GXHomeAutomationBundle:Page", options={"id" = "page_id"})
     */
    public function sortComponentAction(Page $page)
    {
    	$em = $this->getDoctrine()->getManager();
    	
    	$containerId = $this->getRequest()->request->getInt('container_id');
    	if (!($containerId > 0)) throw new \Exception("Sorting components on a unknown container.");
    			
    	$container = $em->getRepository('GXHomeAutomationBundle:Container')->find($containerId);
    	if (!$container) throw new \Exception("Sorting components on a unknown container.");
    	
    	$components = $this->getRequest()->request->get('ids');
    	if (is_array($components)) {
    		try {
	    		array_walk($components, function(&$value, $key) use($em) {
	    			$id = explode('_',$value);
	    			if (sizeof($id) >=2 && $id[1] > 0) {
	    				$id = $id[1];
	    				$value = $em->getRepository('GXHomeAutomationBundle:Component')->find($id);
	    			} else $value = false;
	    		});
	    		$result = Components::sort($container, $components, $em);
	    		return new Response($result?1:0);
    		} catch (Exception $e) {
    			throw new \Exception("Sorting components failed.");
    		}
    	}
    	return new Response(0);
    }
    
    /**
	 * FIXME !10: rework all this
     * @Route("/editDialogComponent/component/{component_id}", name="_component_edit_dialog", requirements={"component_id" = "\d*"})
     * @Method({"GET"})
     * @ParamConverter ("component", class="GXHomeAutomationBundle:Component", options={"id" = "component_id"})
     */
    public function editDialogComponentAction(Component $component)
    {
    	/* @var $myfox MyfoxService */
    	$myfox = $this->get('gx_home_automation.myfox');
    	$type = $component->getType();
    	$home = $component->getContainer()->getPage()->getHome();
    	
    	$template = Components::$constEditTemplates[$type];
    	$params = array('component' => $component);
    	
    	if ($type == 1 || $type == 2 || $type == 6) // scenario_play // scenario_activation
    		$params['scenario_list'] = json_decode($myfox->playSync($home->getHomeKey(), MyfoxCommand::CMD_GET_SCENARIO_ITEMS, array(), true, true), true)['payload']['items'];

    	$content = $this->renderView($template, $params);
    	return new Response($content);
    }
    
    /**
	 * FIXME !10: rework all this
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
     * @Route("/showComponent/{component_id}", name="_component_show",  requirements={"component_id" = "\d*"})
     * @Method({"GET"})
     * @ParamConverter ("component", class="GXHomeAutomationBundle:Component", options={"id" = "component_id"})
     */
    public function showAction(Component $component)
    {
    	$template = Components::$constTemplates[$component->getType()];
    	 
    	$content = $this->renderView(
    			$template,
    			array('component' => $component, 'container' => $component->getContainer())
    	);
    	return new Response($content);
    }

	/**
	 * @Route("/showComponent/", name="_component_show_get")
	 * @Method({"GET","POST"})
	 */
	public function showGetAction(Request $request)
	{
		$componentId = $request->query->get('component_id', $request->query->get('id', null));
		if ($componentId == null) {
			return Response::create('Component not found', 404);
		}

		$em = $this->getDoctrine()->getManager();
		$component = $em->getRepository('GXHomeAutomationBundle:Component')->find($componentId);
		if (!$component) {
			return Response::create('Component not found', 404);
		}

		return $this->showAction($component);
	}
    			
}			