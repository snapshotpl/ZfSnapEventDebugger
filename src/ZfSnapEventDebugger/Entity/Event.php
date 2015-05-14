<?php

namespace ZfSnapEventDebugger\Entity;

/**
 * Event
 *
 * @author Witold Wasiczko <witold@wasiczko.pl>
 */
class Event
{
    protected $id;
    protected $name;

    /**
     * @var TriggerSource
     */
    protected $triggerSource;

    /**
     * @var Listener[]
     */
    protected $listeners = array();

    /**
     * @var Listener[]
     */
    protected $sharedListeners = array();

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getTriggerSource()
    {
        return $this->triggerSource;
    }

    public function getListeners()
    {
        return $this->listeners;
    }

    public function getSharedListeners()
    {
        return $this->sharedListeners;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function setTriggerSource(TriggerSource $triggerSource)
    {
        $this->triggerSource = $triggerSource;
    }

    public function setListeners(array $listeners)
    {
        $this->listeners = $listeners;
    }

    public function setSharedListeners(array $sharedListeners)
    {
        $this->sharedListeners = $sharedListeners;
    }


}
