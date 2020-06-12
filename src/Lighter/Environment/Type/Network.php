<?php

namespace Lighter\Environment\Type;

use Lighter\Environment\EnvironmentInterface;

/**
 * Manage a network supporting the environments
 *
 * @package Lighter\Environment\Type
 */
class Network extends BaseType implements EnvironmentInterface
{
    /**
     * @var bool|null
     */
    private $started;

    /**
     * @var string
     */
    private $networkName = 'public';

    /**
     * Network constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        parent::__construct($config);
        if (array_key_exists('networkName', $config)) {
            $this->networkName = $config['networkName'];
        }
    }

    /**
     * {@inheritDoc}
     */
    public function start(): void
    {
        if (!$this->started) {
            $this->getShell()->exec('docker network create ' . $this->networkName);
            $this->started = true;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function isStarted(bool $fully = false): bool
    {
        if ($this->started === null) {
            $shell = $this->getShell();
            $shell->exec('docker network ls | grep ' . $this->networkName);
            $this->started = $shell->getOutput() !== '';
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
     * @return array
     */
    public function getStatus(): array
    {
        $state = $this->isStarted() ? EnvironmentInterface::STATUS_STARTED : EnvironmentInterface::STATUS_STOPPED;

        return [$this->getName() => $state];
    }

    /**
     * {@inheritDoc}
     */
    public function stop(): void
    {
        if ($this->isStarted()) {
            $this->getShell()->exec('docker network rm ' . $this->networkName);
            $this->started = false;
        }
    }
}
