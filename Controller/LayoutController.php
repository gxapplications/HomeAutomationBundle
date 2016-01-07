<?php

namespace GXApplications\HomeAutomationBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

use GXApplications\HomeAutomationBundle\Entity\Page;
use GXApplications\HomeAutomationBundle\Entity\Container;
use GXApplications\HomeAutomationBundle\Layouts;
use GXApplications\HomeAutomationBundle\Components;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LayoutController extends Controller
{
	
	
    /**
     * @Route("/showLayout/page/{page_id}", name="_home_layout_show")
     * @ParamConverter ("page", class="GXHomeAutomationBundle:Page", options={"id" = "page_id"})
     */
    public function showAction(Page $page)
    {
    	Layouts::fixContainers($page, $this->getDoctrine()->getManager());
    	$containers = Layouts::getIndexedContainers($page);
    	$layout = $page->getLayout();
    	
    	if (!array_key_exists($layout, Layouts::$constTemplates))
    		throw new \Exception('Layout index error.');
    	
		$componentTypes = array();
		$playTpls = Components::$constTemplates;
		$editTpls = Components::$constEditTemplates;
		foreach (Components::$constNames as $k => $component) {
			$componentTypes[$k] = array('name' => $component, 'id' => $k, 'play_url' => $playTpls[$k], 'edit_url' => $editTpls[$k]);
		}
    	
    	$template = Layouts::$constTemplates[$layout];
    	
    	$content = $this->renderView(
    		$template,
    		array('page' => $page, 'containers' => $containers, 'component_types' => $componentTypes)
    	);
    	return new Response($content);
    }
    
    /**
     * @Route("/changeLayout/page/{page_id}", name="_home_layout_change")
     * @Method({"POST"})
     * @ParamConverter ("page", class="GXHomeAutomationBundle:Page", options={"id" = "page_id"})
     */
    public function changeAction(Page $page)
    {
    	$layout = $this->getRequest()->request->getInt('layout');
    	if ($layout > 0) {
    		$page->setLayout($layout);
    		$em = $this->getDoctrine()->getManager();
    		$em->persist($page);
    		$em->flush();
    		Layouts::fixContainers($page, $em);
    		return new Response();
    	} else throw new \Exception("Layout not found.");
    }
    
    /**
     * @Route("/commitLayout/page/{page_id}", name="_home_layout_commit")
     * @Method({"POST"})
     * @ParamConverter ("page", class="GXHomeAutomationBundle:Page", options={"id" = "page_id"})
     */
    public function commitAction(Page $page)
    {
    	$em = $this->getDoctrine()->getManager();
    	
    	$containers = Layouts::getIndexedContainers($page);
    	$containersData = $this->getRequest()->request->get("containers");
    	
    	foreach ($containers as $position => $container) {
    		/* @var $container Container */
    		$border = $containersData['border_'.$position];
    		$title = $containersData['title_'.$position];
    		$container->setBorder($border);
    		$container->setTitle($title);
    		$em->persist($container);
    	}

    	$em->flush();
    	return new Response();
    }

}
