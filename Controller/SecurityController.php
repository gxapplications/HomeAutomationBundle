<?php

namespace GXApplications\HomeAutomationBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Response;
use GXApplications\HomeAutomationBundle\MyfoxService;
use GXApplications\HomeAutomationBundle\Entity\Account;
use GXApplications\HomeAutomationBundle\Entity\Home;

class SecurityController extends Controller
{
    /**
     * @Route("/login", name="_home_login")
     * @Template()
     */
    public function loginAction($from403 = false)
    {
    	$error = false;
        $request = $this->getRequest();
        $session = $request->getSession();
        // get the login error if there is one
        if ($request->attributes->has(SecurityContext::AUTHENTICATION_ERROR)) {
            $error = $request->attributes->get(SecurityContext::AUTHENTICATION_ERROR);
        } else {
            $error = $session->get(SecurityContext::AUTHENTICATION_ERROR);
            $session->remove(SecurityContext::AUTHENTICATION_ERROR);
        }
        
        $em = $this->getDoctrine()->getManager();
        $homes = $em->getRepository('GXHomeAutomationBundle:Home')->findBy(array(), array('last_access' => 'DESC'));
        
        if ($error) return new Response('ERR:'.$error->getMessage());
        
        return array(
            // last username entered by the user
            'last_account_id' => ($homes[0])?$homes[0]->getAccount()->getId():'',
        	'last_home_id'    => ($homes[0])?$homes[0]->getId():'',
        	'homes'			  => $homes,
        	'from403'		  => $from403
        );
    }

    /**
     * @Route("/login_check", name="_home_security_check")
     */
    public function securityCheckAction()
    {
        // The security layer will intercept this request
    }

    /**
     * @Route("/logout", name="_home_logout")
     */
    public function logoutAction()
    {
        // The security layer will intercept this request
    }

}
