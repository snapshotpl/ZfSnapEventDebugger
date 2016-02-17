<?php

namespace ZfSnapEventDebugger;

use Zend\EventManager\EventInterface;
use Zend\EventManager\EventManager;
use Zend\EventManager\EventsCapableInterface;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\EventManager\SharedListenerAggregateInterface;
use Zend\Mvc\MvcEvent;
use Zend\Stdlib\CallbackHandler;
use Zend\Stdlib\PriorityQueue;

/**
 * TriggerEventListener
 *
 * @author Witold Wasiczko <witold@wasiczko.pl>
 */
class TriggerEventListener implements SharedListenerAggregateInterface
{
    const SELF_PARAM_NAME = __CLASS__;
    const NUMBER_STACK_FRAME = 5;
    const WILDCARD = '*';
    const HIGHEST_PRIORITY = 100000000;

    /**
     * @var Entity\Event[]
     */
    protected $events = array();

    /**
     * @var string|null
     */
    protected $getcwd = null;

    /**
     * @var SharedEventManagerInterface
     */
    protected $sharedManager;

    /**
     * @param SharedEventManagerInterface $events
     */
    public function attachShared(SharedEventManagerInterface $events)
    {
        $this->sharedManager = $events;

        $events->attach(self::WILDCARD, self::WILDCARD, array($this, 'onTriggerAnyEvent'), self::HIGHEST_PRIORITY);
        $events->attach('Zend\Mvc\Application', MvcEvent::EVENT_BOOTSTRAP, array($this, 'injectListenerToEvent'));
    }

    public function detachShared(SharedEventManagerInterface $events)
    {
    }

    /**
     * @param MvcEvent $e
     */
    public function injectListenerToEvent(MvcEvent $e)
    {
        $e->setParam(self::SELF_PARAM_NAME, $this);
    }

    /**
     * @param EventInterface $event
     */
    public function onTriggerAnyEvent(EventInterface $event)
    {
        $eventName = $event->getName();
        $target = $event->getTarget();
        $id = get_class($target);
        $name = $this->getEventName($id, $eventName);

        $eventEntity = new Entity\Event();
        $eventEntity->setName($eventName);
        $eventEntity->setId($id);
        $eventEntity->setListeners($this->getEventManagerListeners($event));
        $eventEntity->setSharedListeners($this->getSharedEventManagerListeners($event));
        $triggerSource = $this->getTriggerSource();

        if ($triggerSource instanceof Entity\TriggerSource) {
            $eventEntity->setTriggerSource($triggerSource);
        }

        $this->events[$name] = $eventEntity;
    }

    /**
     * @param EventInterface $event
     * @return Entity\Listener[]
     */
    protected function getEventManagerListeners(EventInterface $event)
    {
        $target = $event->getTarget();
        $eventName = $event->getName();
        $callbacks = array();

        if (is_object($target) && $target instanceof EventsCapableInterface) {
            $em = $target->getEventManager();

            if ($em instanceof EventManager) {
                $listeners = $em->getListeners($eventName);
                $callbacks = $this->getCallbacks($listeners);
            }
        }
        return $callbacks;
    }

    /**
     * @param EventInterface $event
     * @return Entity\Listener[]
     */
    protected function getSharedEventManagerListeners(EventInterface $event)
    {
        $target = $event->getTarget();
        $id = get_class($target);
        $eventName = $event->getName();
        $callbacks = array();
        $sharedListeners = $this->sharedManager->getListeners($id, $eventName);

        if ($sharedListeners !== false) {
            $callbacks = $this->getCallbacks($sharedListeners);
        }
        return $callbacks;
    }

    /**
     * @return Entity\TriggerSource
     */
    protected function getTriggerSource()
    {
        $debugBacktrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $index = self::NUMBER_STACK_FRAME - 1;

        if (isset($debugBacktrace[$index]) && in_array($debugBacktrace[$index]['function'], array('trigger', 'triggerUntil', 'triggerEvent', 'triggerEventUntil'))) {
            $triggerSource = new Entity\TriggerSource();
            $triggerSource->setFilename($this->removeGetcwd($debugBacktrace[$index]['file']));
            $triggerSource->setLine($debugBacktrace[$index]['line']);

            return $triggerSource;
        }
    }

    /**
     * @param PriorityQueue $listeners
     * @return Entity\Listener[]
     */
    protected function getCallbacks(PriorityQueue $listeners)
    {
        $callbacks = array();

        foreach ($listeners as $listener) {
            if ($listener instanceof CallbackHandler) {
                $callbacks[] = $this->getListenerFromCallbackHandler($listener);
            }
        }
        return $callbacks;
    }

    /**
     * @param CallbackHandler $callbackHandler
     * @return Entity\Listener
     */
    protected function getListenerFromCallbackHandler(CallbackHandler $callbackHandler)
    {
        $callback = $callbackHandler->getCallback();
        $priority = (int) $callbackHandler->getMetadatum('priority');

        if ($callback instanceof \Closure) {
            $name = $this->getCallbackIdFromClosure($callback);
        } elseif (is_array($callback) && count($callback) === 2 && is_object($callback[0])) {
            $name = $this->getMethodCall($callback[0], $callback[1]);
        } elseif (is_string($callback)) {
            $name = $callback;
        } elseif (is_object($callback) && is_callable($callback)) {
            $name = $this->getMethodCall($callback, '__invoke');
        } else {
            $name = 'Unknown callback';
        }

        $listener = new Entity\Listener();
        $listener->setName($name);
        $listener->setPriority($priority);

        return $listener;
    }

    /**
     * @param string $id
     * @param string $name
     * @return string
     */
    protected function getEventName($id, $name)
    {
        return sprintf('%s::%s',  $id, $name);
    }

    /**
     * @param \Closure $function
     * @return string
     */
    protected function getCallbackIdFromClosure(\Closure $function)
    {
        $ref = new \ReflectionFunction($function);
        $path = $this->removeGetcwd($ref->getFileName());
        $startLine = $ref->getStartLine();
        $endLine = $ref->getEndLine();

        return sprintf('Closure: %s:%d-%d', $path, $startLine, $endLine);
    }

    /**
     * @param object $object
     * @param string $method
     * @return string
     */
    protected function getMethodCall($object, $method)
    {
        return sprintf('%s::%s()', get_class($object), $method);
    }

    /**
     * @param string $path
     * @return string
     */
    protected function removeGetcwd($path)
    {
        $prefix = $this->getGetcwd();
        if (substr($path, 0, strlen($prefix)) === $prefix) {
            $path = substr($path, strlen($prefix));
        }
        return $path;
    }

    /**
     * @return string
     */
    protected function getGetcwd()
    {
        if ($this->getcwd === null) {
            $this->getcwd = getcwd() .'/';
        }
        return $this->getcwd;
    }

    /**
     * @return Entity\Event[]
     */
    public function getEvents()
    {
        return $this->events;
    }

}
