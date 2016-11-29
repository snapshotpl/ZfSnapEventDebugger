<?php

namespace ZfSnapEventDebugger;

use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\ModuleManager\Feature\ConfigProviderInterface;
use Zend\ModuleManager\Feature\InitProviderInterface;
use Zend\ModuleManager\ModuleManager;
use Zend\ModuleManager\ModuleManagerInterface;

/**
 * Module
 *
 * @author Witold Wasiczko <witold@wasiczko.pl>
 */
class Module implements InitProviderInterface, ConfigProviderInterface, AutoloaderProviderInterface
{
    /**
     * @param ModuleManagerInterface $manager
     */
    public function init(ModuleManagerInterface $manager)
    {
        $moduleManagerEventManager = $manager->getEventManager();

        if (!$moduleManagerEventManager instanceof ModuleManager) {
            return;
        }

        $sharedManager = $moduleManagerEventManager->getSharedManager();

        $listener = new TriggerEventListener();
        $listener->attachShared($sharedManager);

        $manager->loadModule('ZendDeveloperTools');
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return include __DIR__ . '/../../config/module.config.php';
    }

    /**
     * @return array
     */
    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__,
                ),
            ),
        );
    }

}
