<?php

namespace GXApplications\HomeAutomationBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use JMS\SecurityExtraBundle\Annotation\Secure;

use GXApplications\HomeAutomationBundle\Entity\Home;
use GXApplications\HomeAutomationBundle\Entity\Page;
use GXApplications\HomeAutomationBundle\Layouts;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use GXApplications\HomeAutomationBundle\Entity\Account;
use GXApplications\HomeAutomationBundle\Entity\MyfoxCommand;

class PageController extends Controller
{
    /**
     * @Route("/", name="_home")
     * @Secure(roles="ROLE_USER")
     */
    public function indexAction()
    {
    	$em = $this->getDoctrine()->getManager();
    	
    	// find last accessed home, and forward to it.
    	$homes = $em->getRepository('GXHomeAutomationBundle:Home')->findBy(array(),array('last_access'=>'DESC'),1);
    	if(sizeof($homes)>0) {
    		$home = $homes[0];
    		return $this->forward('GXHomeAutomationBundle:Page:home', array('id' => $home->getId()));
    	}
    	// else, go to home creation page.
    	return $this->redirect($this->generateUrl('_account_creation'));
    }
    
    /**
     * @Route("/{id}", requirements={"id" = "\d+"})
     * @ParamConverter ("home", class="GXHomeAutomationBundle:Home")
     * @Secure(roles="ROLE_USER")
     */
    public function homeAction(Home $home)
    {
    	return $this->forward('GXHomeAutomationBundle:Page:page', array(
    			'home_id' => $home->getId(), 'page_id' => $home->getDefaultPage()->getId()
    	));
    }
    
    /**
     * @Route("/{home_id}/page/{page_id}", requirements={"page_id" = "\d+"}, name="_home_page_main")
     * @ParamConverter ("home", class="GXHomeAutomationBundle:Home", options={"id" = "home_id"})
     * @ParamConverter ("page", class="GXHomeAutomationBundle:Page", options={"id" = "page_id"})
     * @Template()
     * @Secure(roles="ROLE_USER")
     */
    public function pageAction(Home $home, Page $page)
    {
    	if ($page->getHome()->getId() != $home->getId()) throw $this->createNotFoundException("Page is not linked to corresponding Home.");
    	
    	if (false === $this->get('security.context')->isGranted('ROLE_ACCOUNT_'.$home->getAccount()->getId())) {
    		throw $this->createAccessDeniedException();
    	}
    	
    	// update last home access time
    	$em = $this->getDoctrine()->getManager();
    	$home->setLastAccess(new \DateTime());
    	$em->persist($home);
    	$em->flush();
    	
    	return array('home' => $home, 'page' => $page);
    }
    
    /**
     * @Route("/commitPage/{page_id}", name="_home_page_commit")
     * @Method({"POST"})
     * @ParamConverter ("page", class="GXHomeAutomationBundle:Page", options={"id" = "page_id"})
     * @Secure(roles="ROLE_USER")
     */
    public function commitPageAction(Page $page, Request $request)
    {
    	$em = $this->getDoctrine()->getManager();
    	 
    	$pageData = $request->request->get("page");

		if (array_key_exists('name', $pageData))
    		$page->setName($pageData['name']);
		if (array_key_exists('layout', $pageData))
			$page->setLayout($pageData['layout']);
		if (array_key_exists('positions', $pageData))
			$page->setPositions($pageData['positions']);
    	$em->persist($page);
    
    	$em->flush();
    	return new Response();
    }
    
    /**
     * @Route("/accountManager", name="_account_manager")
     * @Template()
     * @Secure(roles="ROLE_ADMIN")
     */
    public function accountManagerAction(Request $request)
    {
    	$em = $this->getDoctrine()->getManager();
    	
    	if ($request->isMethod('POST') && $request->get('update_pass', 0) == 1) {
    		$data = $request->get('account', array(), true);
    	
    		/* @var $home Home */
    		$home = $em->getRepository('GXHomeAutomationBundle:Home')->find($data['home_id']);
    		if (!$home) throw new \Exception("Home not found.");
    		$account = $home->getAccount();
    		if (!$account) throw new \Exception("Home without account! Please correct this.");
    		
    		$account->setClearAccountPassword($data['password'], $data['pattern']);
    		// pattern encoded
    		$factory = $this->get('security.encoder_factory');
    		$encoder = $factory->getEncoder($account);
    		$pattern = $encoder->encodePassword($data['pattern'], $account->getSalt());
    		$account->setPattern($pattern);
    	
    		$em->persist($account);
    		$em->flush();

    		return new Response('1');
    	}
    	
    	$homes = $em->getRepository('GXHomeAutomationBundle:Home')->findBy(array(), array('last_access' => 'DESC'));
    	return array(
    			'homes' => $homes,
    	);
    }
    
    /**
     * @Route("/accountCreation", name="_account_creation")
     * @Template()
     * @Secure(roles="ROLE_ADMIN")
     */
    public function accountCreationAction(Request $request)
    {
    	$em = $this->getDoctrine()->getManager();
    	
    	if ($request->isMethod('POST')) {
    		$data = $request->get('account', array(), true);
    		
    		// account, match by login if any. Create otherwise.
    		$account = $em->getRepository('GXHomeAutomationBundle:Account')->findBy(array('account_login' => $data['my_email']), array('id'=>'DESC'), 1);
    		if (!$account || sizeof($account)!=1 || $account[0]==null) $account = new Account();
    		else $account = $account[0];
    		$account->setAccountLogin($data['my_email'])->setName($data['my_email']);
    		$account->setClearAccountPassword($data['my_pass'], $data['pattern']);
    		// pattern encoded
    		$factory = $this->get('security.encoder_factory');
    		$encoder = $factory->getEncoder($account);
    		$pattern = $encoder->encodePassword($data['pattern'], $account->getSalt());
    		$account->setPattern($pattern);
    		
    		// home
    		$home = new Home();
    		$home->setName($data['home_name'])->setHomeKey($data['home_id']);
    		$home->setLastAccess(new \DateTime())->setAccount($account);
    		$account->addHome($home);
    		
    		// page
    		$page = new Page();
    		$page->setName($data['page_label'])->setHome($home)->setLayout(4);
    		$home->setDefaultPage($page);
    		
    		$em = $this->getDoctrine()->getManager();
    		$em->persist($account);
    		$em->persist($page); // no persist cascaded, so need to persist manually.
    		$em->flush();
    		
    		// ajax call return
    		//return new Response($this->generateUrl('_home_page_main',array("home_id" => $home->getId(), "page_id" => $page->getId())));
    		return new Response($this->generateUrl('_home_logout'));
    	}
    	
    	// find last account inserted for prefilled form
    	$accounts = $em->getRepository('GXHomeAutomationBundle:Account')->findBy(array(),array('id'=>'DESC'),1);
    	return array("last_email" => (sizeof($accounts)>0)?($accounts[0]->getAccountLogin()):"");
    }
    
    /**
     * @Route("/md_admin_bottom_sheet", name="_md_admin_bottom_sheet")
     * @Template()
     * @Secure(roles="ROLE_ADMIN")
     */
    public function mdAdminBottomSheetAction(Request $request)
    {
    	if ($request->isMethod('POST')) {
    		$em = $this->getDoctrine()->getManager();
    		$action = $request->get('action_id', null);
    		
    		switch($action) {
    			
    			case 'delete_home':
    				$homeId = $request->get('home_id', null);
    				if (!$homeId) throw new \Exception("Home not found.");
    				$home = $em->getRepository('GXHomeAutomationBundle:Home')->find($homeId);
    				if (!$home) throw new \Exception("Home not found.");
    				
    				/* @var $home Home */
    				$account = $home->getAccount();
    				// if account is about to loose its last home, remove it too.
    				if (sizeof($account->getHomes()) == 1) $em->remove($account);
    				
    				$em->remove($home);
    				
    				$em->flush();
    				return new Response(1);
    				break;
    				
    			default:
    				return array();
    		}
    	} else return array();
    }
}
