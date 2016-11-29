<?php

namespace ZfSnapEventDebugger\Entity;

/**
 * Listener
 *
 * @author Witold Wasiczko <witold@wasiczko.pl>
 */
class Listener
{
    protected $priority;
    protected $name;

    public function getPriority()
    {
        return $this->priority;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setPriority($priority)
    {
        $this->priority = $priority;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

}
