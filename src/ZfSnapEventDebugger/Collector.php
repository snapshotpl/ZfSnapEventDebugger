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
        $listener = $mvcEvent->getParam(TriggerEventListener::SELF_PARAM_NAME);

        $this->data = $listener->getEvents();
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
