<?php

namespace ZfSnapEventDebugger;

use Zend\EventManager\EventInterface;
use Zend\EventManager\EventManager;
use Zend\EventManager\EventsCapableInterface;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\Stdlib\CallbackHandler;
use Zend\Stdlib\PriorityQueue;

/**
 * TriggerEventListener
 *
 * @author Witold Wasiczko <witold@wasiczko.pl>
 */
class TriggerEventListener
{
    const WILDCARD = '*';
    const MAX_PRIORITY = 100000000;

    /**
     * @var SharedEventManagerInterface
     */
    protected $sharedManager;

    /**
     * @var array
     */
    protected $events = array();

    /**
     * @var string|null
     */
    protected $getcwd = null;

    public function __construct(SharedEventManagerInterface $sharedManager)
    {
        $this->sharedManager = $sharedManager;

        $sharedManager->attach(self::WILDCARD, self::WILDCARD, $this, self::MAX_PRIORITY);
    }

    /**
     * @param EventInterface $e
     */
    public function __invoke(EventInterface $e)
    {
        $target = $e->getTarget();
        $id = get_class($target);
        $eventName = $e->getName();

        $this->events[$this->getEventName($id, $eventName)] = array(
            'Called in ' => $this->getCalledTrace(),
            'EventManager' => $this->getEventManagerCallbacks($target, $eventName),
            'SharedEventManger' => $this->getSharedEventManagerCallbacks($id, $eventName),
        );
    }

    /**
     * @param EventsCapableInterface $target
     * @param string $eventName
     * @return array
     */
    protected function getEventManagerCallbacks($target, $eventName)
    {
        $eventManagerCallbacks = array();

        if (is_object($target) && $target instanceof EventsCapableInterface) {
            $em = $target->getEventManager();

            if ($em instanceof EventManager) {
                $listeners = $em->getListeners($eventName);
                $eventManagerCallbacks = $this->getCallbacks($listeners);
            }
        }
        return $eventManagerCallbacks;
    }

    /**
     * @param string $id
     * @param string $eventName
     * @return array
     */
    protected function getSharedEventManagerCallbacks($id, $eventName)
    {
        $sharedEventMangerCallbacks = array();

        $sharedListeners = $this->sharedManager->getListeners($id, $eventName);

        if ($sharedListeners !== false) {
            $sharedEventMangerCallbacks = $this->getCallbacks($sharedListeners);
        }
        return $sharedEventMangerCallbacks;
    }

    /**
     * @return string
     */
    protected function getCalledTrace()
    {
        $calledTrace = 'Unknown';
        $debugBacktrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        if (isset($debugBacktrace[4]) && $debugBacktrace[4]['function'] === 'trigger') {
            $calledTrace = $this->removeGetcwd($debugBacktrace[4]['file']) . ':' . $debugBacktrace[4]['line'];
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
        return $id . '::' . $name;
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

        $callbackName = $this->callbackToString($callback);

        return array(
            'callback' => $callbackName,
            'priority' => $priority,
        );
    }

    /**
     * @param mixed $callback
     * @return string
     */
    protected function callbackToString($callback)
    {
        if ($callback instanceof \Closure) {
            $ref = new \ReflectionFunction($callback);
            $callbackName = 'Closure: ' . $this->removeGetcwd($ref->getFileName()) . ':' . $ref->getStartLine() . '-' . $ref->getEndLine();
        } elseif (is_array($callback) && count($callback) === 2 && is_object($callback[0])) {
            $callbackName = $this->getMethodCall($callback[0], $callback[1]);
        } elseif (is_string($callback)) {
            $callbackName = $callback;
        } elseif (is_object($callback) && is_callable($callback)) {
            $callbackName = $this->getMethodCall($callback, '__invoke()');
        } else {
            $callbackName = 'Unknown callback';
        }
        return $callbackName;
    }

    /**
     * @param object $object
     * @param string $method
     * @return string
     */
    protected function getMethodCall($object, $method)
    {
        return get_class($object) . '::' . $method;
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
            $this->getcwd = getcwd() . '/';
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
