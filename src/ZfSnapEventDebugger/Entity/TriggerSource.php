<?php

namespace ZfSnapEventDebugger\Entity;

/**
 * TriggerSource
 *
 * @author Witold Wasiczko <witold@wasiczko.pl>
 */
class TriggerSource
{
    protected $filename;
    protected $line;

    public function getFilename()
    {
        return $this->filename;
    }

    public function getLine()
    {
        return $this->line;
    }

    public function setFilename($filename)
    {
        $this->filename = $filename;
    }

    public function setLine($line)
    {
        $this->line = $line;
    }

}
