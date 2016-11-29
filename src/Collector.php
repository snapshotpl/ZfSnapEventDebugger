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
    /**
     * @param MvcEvent $mvcEvent
     */
    public function collect(MvcEvent $mvcEvent)
    {
        $listener = $mvcEvent->getParam(TriggerEventListener::SELF_PARAM_NAME);

        $this->data = $listener->getEvents();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return __CLASS__;
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return -1000;
    }

    /**
     * @return Entity\Event[]
     */
    public function getResult()
    {
        return $this->data;
    }

}
