<?php

namespace GXApplications\HomeAutomationBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class GXHomeAutomationExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $fileLocator = new FileLocator(__DIR__.'/../Resources/config');
        
        $loader = new Loader\XmlFileLoader($container, $fileLocator);
        $loader->load('services.xml');
        
        if (!isset($config['client_id']) || !isset($config['client_secret'])) {
        	throw new \InvalidArgumentException('The "gx_home_automation.client_id" and "gx_home_automation.client_secret" options must be set');
        }
        
        $container->setParameter('gx_home_automation.client_id', $config['client_id']);
        $container->setParameter('gx_home_automation.client_secret', $config['client_secret']);
    }
}
