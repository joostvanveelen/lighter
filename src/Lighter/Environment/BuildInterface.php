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
     */
    public function build(): void;
}
