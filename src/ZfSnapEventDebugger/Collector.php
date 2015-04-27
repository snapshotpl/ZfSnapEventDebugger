<?php

namespace ZfSnapEventDebugger;

use Zend\Mvc\MvcEvent;
use ZendDeveloperTools\Collector\AbstractCollector;

/**
 * Collector
 *
 * @author Witold Wasiczko <witold@wasiczko.pl>
 */
class Collector extends AbstractCollector
{
    public function collect(MvcEvent $mvcEvent)
    {
        $sm = $mvcEvent->getApplication()->getServiceManager();
        /* @var $moduleManager \Zend\ModuleManager\ModuleManager */
        $moduleManager = $sm->get('ModuleManager');

        $modules = $moduleManager->getLoadedModules(false);

        if (isset($modules[__NAMESPACE__]) && $modules[__NAMESPACE__] instanceof Module) {
            $this->data = $modules[__NAMESPACE__]->getListener()->getEvents();
        }
    }

    public function getName()
    {
        return 'eventListeners';
    }

    public function getPriority()
    {
        return 100;
    }

    public function getResult()
    {
        return $this->data;
    }

}
