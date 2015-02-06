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
 * EventDebuggerListener
 *
 * @author Witold Wasiczko <witold@wasiczko.pl>
 */
class EventDebuggerListener implements SharedListenerAggregateInterface
{
    const SELF_PARAM_NAME = 'EventDebuggerListener';
    const HIGHEST_PRIORITY = 100000000;
    const NUMBER_STACK_FRAME = 5;

    /**
     * @var array
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

        $events->attach('*', '*', array($this, 'onTriggerAnyEvent'), self::HIGHEST_PRIORITY);
        $events->attach('Zend\Mvc\Application', MvcEvent::EVENT_BOOTSTRAP, array($this, 'injectListener'));
    }

    public function detachShared(SharedEventManagerInterface $events)
    {
    }

    /**
     * @param MvcEvent $e
     */
    public function injectListener(MvcEvent $e)
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
        $eventKey = $this->getEventName($id, $eventName);

        $this->events[$eventKey] = array(
            'Called in ' => $this->getCallerTrace(),
            'EventManager' => $this->getEventManagerCallbacks($event),
            'SharedEventManger' => $this->getSharedEventManagerCallbacks($event),
        );
    }

    /**
     * @param EventInterface $event
     * @return array
     */
    protected function getEventManagerCallbacks(EventInterface $event)
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
     * @return array
     */
    protected function getSharedEventManagerCallbacks(EventInterface $event)
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
     * @return string
     */
    protected function getCallerTrace()
    {
        $calledTrace = 'Unknown';
        $debugBacktrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, self::NUMBER_STACK_FRAME);

        $index = self::NUMBER_STACK_FRAME - 1;

        if (isset($debugBacktrace[$index]) && $debugBacktrace[$index]['function'] === 'trigger') {
            $calledTrace = $this->removeGetcwd($debugBacktrace[$index]['file']) .':'.$debugBacktrace[$index]['line'];
        }
        return $calledTrace;
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
     * @param PriorityQueue $listeners
     * @return array[]
     */
    protected function getCallbacks(PriorityQueue $listeners)
    {
        $callbacks = array();

        foreach ($listeners as $listener) {
            if ($listener instanceof CallbackHandler) {
                $callbacks[] = $this->getCallbackFromListener($listener);
            }
        }
        return $callbacks;
    }

    /**
     * @param CallbackHandler $listener
     * @return array
     */
    protected function getCallbackFromListener(CallbackHandler $listener)
    {
        $callback = $listener->getCallback();
        $priority = (int) $listener->getMetadatum('priority');

        if ($callback instanceof Closure) {
            $ref = new ReflectionFunction($callback);
            $callbackId = 'Closure: '. $this->removeGetcwd($ref->getFileName()) .':'. $ref->getStartLine() .'-'. $ref->getEndLine();
        } elseif (is_array($callback) && count($callback) === 2 && is_object($callback[0])) {
            $callbackId = $this->getMethodCall($callback[0], $callback[1]);
        } elseif (is_string($callback)) {
            $callbackId = $callback;
        } elseif (is_object($callback) && is_callable($callback)) {
            $callbackId = $this->getMethodCall($callback, '__invoke()');
        } else {
            $callbackId = 'Unknown callback';
        }
        return array(
            'callback' => $callbackId,
            'priority' => $priority,
        );
    }

    /**
     * @param object $object
     * @param string $method
     * @return string
     */
    protected function getMethodCall($object, $method)
    {
        return sprintf('%s::%s', get_class($object), $method);
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
     * @return array
     */
    public function getEvents()
    {
        return $this->events;
    }

}
