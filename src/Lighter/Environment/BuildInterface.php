<?php

namespace Lighter\Environment;

/**
 * Interface BuildableInterface
 *
 * @package Lighter\Environment
 */
interface BuildInterface
{
    /**
     * Builds the environment.
     *
     * @return bool true when the build succeeded.
     */
    public function build(): bool;
}
