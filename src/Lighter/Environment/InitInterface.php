<?php

namespace Lighter\Environment;

interface InitInterface
{
    /**
     * Returns true when there are init containers to run.
     *
     * @return bool
     */
    public function hasInitContainers(): bool;

    /**
     * Runs the init containers.
     */
    public function runInitContainers(): void;
}
