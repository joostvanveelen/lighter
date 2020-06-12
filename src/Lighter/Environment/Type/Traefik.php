<?php

namespace Lighter\Environment\Type;

use Lighter\Environment\EnvironmentInterface;

/**
 * Manage a Traefik router/proxy thing
 *
 * @package Lighter\Environment\Type
 */
class Traefik extends BaseType implements EnvironmentInterface
{
    /**
     * @var bool|null
     */
    private $started;

    /**
     * @var string|null
     */
    private $containerId;

    /**
     * {@inheritDoc}
     */
    public function start(): void
    {
        if (!$this->isStarted()) {
            $this->getShell()->exec('docker run -d --rm --name router -v /var/run/docker.sock:/var/run/docker.sock --network public -p 80:80 -p 8080:8080 traefik:1.7-alpine --api --docker --docker.exposedbydefault=false');
            $this->started = true;
            $this->containerId = $this->getShell()->getOutput(0);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function isStarted(bool $fully = false): bool
    {
        if ($this->started === null) {
            $this->updateStatus();
        }

        return $this->started;
    }

    /**
     * {@inheritDoc}
     */
    public function hasError(): bool
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function getStatus(): array
    {
        $state = $this->isStarted() ? EnvironmentInterface::STATUS_STARTED : EnvironmentInterface::STATUS_STOPPED;

        return [$this->getName() => $state];
    }

    /**
     * Update the status of the Traefic container
     */
    private function updateStatus(): void
    {
        $shell = $this->getShell();
        $shell->exec("docker ps | grep traefik | awk '{print \$1;}'");
        if ($shell->getOutput() !== '') {
            $this->started = true;
            $this->containerId = $shell->getOutput();
        } else {
            $this->started = false;
            $this->containerId = null;
        }
    }

    /**
     * Retrieve the container id of the currently running Traefic container (if any)
     *
     * @return string|null
     */
    private function getContainerId(): ?string
    {
        return $this->containerId;
    }

    /**
     * {@inheritDoc}
     */
    public function stop(): void
    {
        $containerId = $this->getContainerId();
        if ($containerId !== null && $this->isStarted()) {
            $this->getShell()->exec("docker stop ${containerId}");
            $this->started = false;
            $this->containerId = null;
        }
    }
}
