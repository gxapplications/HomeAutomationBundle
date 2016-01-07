<?php

namespace GXApplications\HomeAutomationBundle;

use GXApplications\HomeAutomationBundle\Entity\Container;
use Doctrine\ORM\EntityManager;
use GXApplications\HomeAutomationBundle\Entity\Component;

class Layouts
{
	const ONE_COL = 1;
	const TWO_COLS_50_50 = 2;
	const TWO_COLS_40_60 = 3;
	const TWO_COLS_60_40 = 4;
	
	public static $constColumnCount = array(
			self::ONE_COL => 1,
			self::TWO_COLS_50_50 => 2,
			self::TWO_COLS_40_60 => 2,
			self::TWO_COLS_60_40 => 2
	);
	
	public static $constNames = array(
			self::ONE_COL => 'One column, linear',
			self::TWO_COLS_50_50 => 'Two columns, 50-50%',
			self::TWO_COLS_40_60 => 'Two columns, 40-60%',
			self::TWO_COLS_60_40 => 'Two columns, 60-40%'
	);
	
	public static $constTemplates = array(
			self::ONE_COL => 'GXHomeAutomationBundle:Layout:one_col.html.twig',
			self::TWO_COLS_50_50 => 'GXHomeAutomationBundle:Layout:two_cols_50_50.html.twig',
			self::TWO_COLS_40_60 => 'GXHomeAutomationBundle:Layout:two_cols_40_60.html.twig',
			self::TWO_COLS_60_40 => 'GXHomeAutomationBundle:Layout:two_cols_60_40.html.twig'
	);
	
	public static function enum() {
        $reflect = new \ReflectionClass( get_called_class() );
        return $reflect->getConstants();
    }
    
    /**
     * @param Page $page
     * @param EntityManager $em
     */
    public static function fixContainers(&$page, EntityManager $em) {
    	$layout = $page->getLayout();
    	$containers = self::getIndexedContainers($page);
    	
    	// no layout, should fix it and retry
    	if ($layout == 0) {
    		$page->setLayout(Layouts::ONE_COL);
	    	$em->persist($page);
	    	$em->flush();
	    	self::fixContainers($page, $em);
	    	return;
    	}
    	
    	$columnCount = self::$constColumnCount[$layout];
    	
    	// when there is missing containers
    	if (sizeof($containers) < $columnCount) {
    		for($i = 0; $i<sizeof($containers); $i++) {
    			$containers[$i]->setLayoutPosition($i);
    			$em->persist($containers[$i]);
    		}
    		for($i = sizeof($containers); $i<$columnCount+1; $i++) {
    			$container = new Container();
    			$container->setLayoutPosition($i)->setPage($page)->setBorder(0);
    			$em->persist($container);
    		}
    		$em->flush();
    		$em->refresh($page); // FIXME: toujours necessaire ? Tester sans
    		$containers = self::getIndexedContainers($page);
    	}
    	
    	// when there is too many containers
    	if (sizeof($containers) > $columnCount) {
    		for ($i = $columnCount; $i < sizeof($containers); $i++) {
    			/* @var $container Container */
    			$container = $containers[$i];
    			foreach($container->getComponents() as $component) {
    				/* @var $component Component */
    				Components::moveToTrail($component, $containers[0], $em);
    			}
    		}
    	}
    	
    	$em->persist($page);
    	$em->flush();
    }
    
    /**
     * @param Page $page
     */
    public static function getIndexedContainers($page) {
    	$containers = $page->getContainers();
    	if ($containers->count() == 0) return array();
    	$containersPositions = array();
    	foreach($containers as $container) {
    		/* @var $container Container */
    		$containersPositions[] = $container->getLayoutPosition();
    	}
    	return array_combine($containersPositions, $containers->toArray());
    }
}
