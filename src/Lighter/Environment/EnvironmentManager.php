<?php

namespace Lighter\Environment;

use RuntimeException;

/**
 * Manage a list of environments.
 *
 * @package Lighter\Environment
 */
class EnvironmentManager
{
    /**
     * @var EnvironmentInterface[]
     */
    private $environments = [];

    /**
     * Adds a new environment to the list.
     *
     * @param EnvironmentInterface $environment
     */
    public function addEnvironment(EnvironmentInterface $environment): void
    {
        foreach ($this->environments as $existingEnvironment) {
            $existingEnvironment->notify($environment);
            $environment->notify($existingEnvironment);
        }

        $this->environments[] = $environment;

    }

    /**
     * Retrieve all the names of the environments.
     *
     * @return string[]
     */
    public function getEnvironmentNames(): array
    {
        $names = [];
        foreach ($this->environments as $environment) {
            $names[] = $environment->getName();
        }

        return $names;
    }

    /**
     * Retrieve an environment by name.
     *
     * @param string $name
     *
     * @return EnvironmentInterface
     *
     * @throws RuntimeException
     */
    public function getEnvironmentByName(string $name): EnvironmentInterface
    {
        foreach ($this->environments as $environment) {
            if ($name === $environment->getName()) {
                return $environment;
            }
        }

        throw new RuntimeException('Environment ' . $name . ' not found.');
    }

    /**
     * Retrieve the environments depending on the given environment.
     *
     * @param EnvironmentInterface $environment
     *
     * @return EnvironmentInterface[]
     */
    public function getReverseDependencies(EnvironmentInterface $environment): array
    {
        $reverseDependencies = [];
        foreach ($this->environments as $env) {
            if (in_array($environment->getName(), $env->getDependencies(), true)) {
                $reverseDependencies[] = $env;
            }
        }

        return $reverseDependencies;
    }
}
