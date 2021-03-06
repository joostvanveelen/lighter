<?php

namespace Lighter\Environment\Type;

use Lighter\Environment\EnvironmentInterface;
use Lighter\Shell;

/**
 * Base environment type, managing metadata such as name, description, etc.
 *
 * @package Lighter\Environment\Type
 */
class BaseType
{
    /**
     * @var Shell
     */
    protected $shell;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $description;

    /**
     * @var array
     */
    private $dependencies;

    /**
     * AbstractEnvironment constructor.
     *
     * @param Shell $shell
     * @param array $config
     */
    public function __construct(Shell $shell, array $config)
    {
        $this->shell = $shell;
        $name = $config['name'] ?? $config['type'];
        $description = $config['description'] ?? $name;
        $dependencies = $config['dependencies'] ?? [];
        $this->name = $name;
        $this->description = $description;
        $this->dependencies = $dependencies;
    }

    protected function getShell(): Shell
    {
        return $this->shell;
    }

    /**
     * @return mixed
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return array
     */
    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    /**
     * Receive a notification of another environment being added to the system.
     *
     * @param EnvironmentInterface $environment
     */
    public function notify(EnvironmentInterface $environment): void
    {
    }
}
