<?php

namespace ZfSnapEventDebugger;

use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\ModuleManager\Feature\ConfigProviderInterface;
use Zend\ModuleManager\Feature\InitProviderInterface;
use Zend\ModuleManager\ModuleManagerInterface;

/**
 * Module
 *
 * @author Witold Wasiczko <witold@wasiczko.pl>
 */
class Module implements InitProviderInterface, ConfigProviderInterface, AutoloaderProviderInterface
{
    /**
     * @var TriggerEventListener
     */
    protected $listener;

    /**
     * @param ModuleManagerInterface $manager
     */
    public function init(ModuleManagerInterface $manager)
    {
        $manager->loadModule('ZendDeveloperTools');

        $sharedManager = $manager->getEventManager()->getSharedManager();
        $sharedManager->attachAggregate(new TriggerEventListener());

//        $this->listener = new TriggerEventListener();
    }

    /**
     * @return TriggerEventListener
     */
    public function getListener()
    {
        if (!$this->listener instanceof TriggerEventListener) {
            throw new \BadMethodCallException('Method can not be called before init() method');
        }
        return $this->listener;
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
