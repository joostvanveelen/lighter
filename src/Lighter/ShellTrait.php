<?php

namespace Lighter;

/**
 * Trait ShellTrait
 * @package Lighter
 */
trait ShellTrait
{
    private $shell;

    /**
     * @return Shell
     */
    protected function getShell()
    {
        if ($this->shell === null) {
            $this->shell = new Shell();
        }

        return $this->shell;
    }
}
