<?php

namespace Lighter\Environment;

use Lighter\Shell;

interface ShellInterface
{
    /**
     * Check if this environment supports a shell
     *
     * @return bool
     */
    public function canShell(): bool;

    /**
     * Run a command
     *
     * @param string $command
     *
     * @return Shell
     */
    public function run(string $command): Shell;
}
