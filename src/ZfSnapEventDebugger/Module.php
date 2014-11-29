<?php

namespace ZfSnapEventDebugger;

use Zend\EventManager\EventInterface;
use Zend\EventManager\EventManager;
use Zend\EventManager\EventsCapableInterface;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\ModuleManager\Feature\ConfigProviderInterface;
use Zend\ModuleManager\Feature\InitProviderInterface;
use Zend\ModuleManager\ModuleManagerInterface;
use Zend\Stdlib\CallbackHandler;
use Zend\Stdlib\PriorityQueue;

/**
 * Module
 *
 * @author Witold Wasiczko <witold@wasiczko.pl>
 */
class Module implements InitProviderInterface, ConfigProviderInterface, AutoloaderProviderInterface
{
    /**
     * @var array
     */
    protected $events = array();

    /**
     * @var string|null
     */
    protected $getcwd = null;

    /**
     * @param ModuleManagerInterface $manager
     */
    public function init(ModuleManagerInterface $manager)
    {
        $sharedManager = $manager->getEventManager()->getSharedManager();
        $self = $this;
        $sharedManager->attach('*', '*', function($e) use ($self, $sharedManager) {
            $self->onTriggerEvent($e, $sharedManager);
        }, 100000000);
    }

    /**
     * @param EventInterface $e
     * @param SharedEventManagerInterface $sharedManager
     */
    public function onTriggerEvent(EventInterface $e, SharedEventManagerInterface $sharedManager)
    {
        $target = $e->getTarget();
        $id = get_class($target);
        $eventName = $e->getName();
        $sharedEventMangerCallbacks = array();
        $eventManagerCallbacks = array();
        $calledTrace = 'Unknown';
        $debugBacktrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        if (isset($debugBacktrace[4]) && $debugBacktrace[4]['function'] === 'trigger') {
            $calledTrace = $this->removeGetcwd($debugBacktrace[4]['file']) .':'.$debugBacktrace[4]['line'];
        }

        if (is_object($target) && $target instanceof EventsCapableInterface) {
            $em = $target->getEventManager();

            if ($em instanceof EventManager) {
                $listeners = $em->getListeners($eventName);
                $eventManagerCallbacks = $this->getCallbacks($listeners);
            }
        }

        $sharedListeners = $sharedManager->getListeners($id, $eventName);

        if ($sharedListeners !== false) {
            $sharedEventMangerCallbacks = $this->getCallbacks($sharedListeners);
        }
        $this->events[$this->getEventName($id, $eventName)] = array(
            'Called in ' => $calledTrace,
            'EventManager' => $eventManagerCallbacks,
            'SharedEventManger' => $sharedEventMangerCallbacks,
        );
    }

    /**
     * @param string $id
     * @param string $name
     * @return string
     */
    protected function getEventName($id, $name)
    {
        return $id .'::'. $name;
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

        if ($callback instanceof \Closure) {
            $ref = new \ReflectionFunction($callback);
            $callbackId = 'Closure: '. $this->removeGetcwd($ref->getFileName()) .':'. $ref->getStartLine() .'-'. $ref->getEndLine();
        } elseif (is_array($callback) && count($callback) === 2 && is_object($callback[0])) {
            $callbackId = get_class($callback[0]) . '::' . $callback[1];
        } elseif (is_string($callback)) {
            $callbackId = $callback;
        } else {
            $callbackId = 'Unknown callback';
        }
        return array(
            'callback' => $callbackId,
            'priority' => $priority,
        );
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
