<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

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
