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
        $param = EventDebuggerListener::SELF_PARAM_NAME;
        /* @var $listener EventDebuggerListener */
        $listener = $mvcEvent->getParam($param);

        $this->data = $listener === null ? array() : $listener->getEvents();
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
