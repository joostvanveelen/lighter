<?php

namespace Lighter\Environment;

/**
 * Interface EnvironmentInterface
 *
 * @package Lighter\Environment
 */
interface EnvironmentInterface
{
    public const
        STATUS_STOPPED = 1,
        STATUS_STARTED = 2,
        STATUS_FAILED = 3;

    /**
     * Returns the name of the environment.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Returns the description of the environment.
     *
     * @return string
     */
    public function getDescription(): string;

    /**
     * Starts the environment.
     */
    public function start(): void;

    /**
     * Stops the environment.
     */
    public function stop(): void;

    /**
     * Returns the running status of the environment.
     *
     * @param bool $fully return true only when *all* components are running.
     *
     * @return bool true if there are running components, false when there are no running components.
     */
    public function isStarted(bool $fully = false): bool;

    /**
     * Returns the error status of the environment.
     *
     * @return bool true when the environment is in an error state, false otherwise
     */
    public function hasError(): bool;

    /**
     * Returns the status of each component of the environment
     *
     * @return array
     */
    public function getStatus(): array;

    /**
     * @return array
     */
    public function getDependencies(): array;

    /**
     * Receive a notification of another environment being added to the system.
     *
     * @param EnvironmentInterface $environment
     */
    public function notify(EnvironmentInterface $environment): void;

}
