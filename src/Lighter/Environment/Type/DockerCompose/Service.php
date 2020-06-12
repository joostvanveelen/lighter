<?php

namespace Lighter\Environment\Type\DockerCompose;

/**
 * Service value-object
 */
class Service
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $command;

    /**
     * @var string
     */
    private $state;

    /**
     * @var string|null
     */
    private $ports;

    /**
     * Service constructor.
     * @param string $name
     * @param string $command
     * @param string $state
     * @param string|null $ports
     */
    public function __construct(string $name, string $command, string $state, ?string $ports)
    {
        $this->name = $name;
        $this->command = $command;
        $this->state = $state;
        $this->ports = $ports;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getCommand(): string
    {
        return $this->command;
    }

    /**
     * @return string
     */
    public function getState(): string
    {
        return $this->state;
    }

    /**
     * @return string|null
     */
    public function getPorts(): ?string
    {
        return $this->ports;
    }
}
