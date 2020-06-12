<?php

namespace Lighter\Environment\Type;

use Lighter\Environment\BuildInterface;
use Lighter\Environment\EnvironmentInterface;
use Lighter\Environment\InitInterface;
use Lighter\Environment\ShellInterface;
use Lighter\Environment\Type\DockerCompose\OutputParser;
use Lighter\Shell;
use RuntimeException;
use Symfony\Component\Yaml\Yaml;

/**
 * Manage a docker compose environment.
 *
 * @package Lighter\Environment\Type
 */
class DockerCompose extends BaseType implements EnvironmentInterface, BuildInterface, InitInterface, ShellInterface
{
    /**
     * @var OutputParser
     */
    private $outputParser;

    /**
     * @var string
     */
    private $path;

    /**
     * @var array
     */
    private $containers;

    /**
     * @var array
     */
    private $initContainers;

    /**
     * @var string|null
     */
    private $shell;

    /**
     * @var array|null
     */
    private $dockerComposeConfig;

    /**
     * @var array|null
     */
    private $state;

    /**
     * @var bool keep track of reset actions
     */
    private $resettingSisters = false;

    /**
     * @var DockerCompose[] Keep track of sister environments (environments that share the same path)
     */
    private $sisters = [];

    /**
     * Environment constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        parent::__construct($config);
        $this->outputParser = new OutputParser();
        $this->path = $config['path'] ?? '';
        $this->containers = $config['containers'] ?? [];
        $this->initContainers = $config['initContainers'] ?? [];
        $shell = $config['shell'] ?? null;
        if ($shell && in_array($shell, $this->containers, true)) {
            $this->shell = $shell;
        } else {
            $this->shell = null;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function start(): void
    {
        chdir($this->path);
        $containers = implode(' ', $this->containers);
        $shell = $this->getShell();
        $shell->exec("docker-compose up -d {$containers}");
        if ($shell->getStatus() !== 0) {
            throw new RuntimeException("Environment '{$this->getName()}' failed to start: {$shell->getOutput()}");
        }
        $this->resetState();
    }

    /**
     * {@inheritDoc}
     */
    public function stop(): void
    {
        if ($this->isStarted()) {
            chdir($this->path);
            if ($this->hasSisters() && $this->hasOtherRunningContainers()) {
                $containers = implode(' ', $this->containers);
                $this->getShell()->exec("docker-compose stop {$containers}");
            } else {
                $this->getShell()->exec('docker-compose down');
            }
        }
        $this->resetState();
    }

    /**
     * Returns true when there are sisters to this environment. Sister environments share the same path.
     *
     * @return bool
     */
    private function hasSisters(): bool
    {
        return count($this->sisters) > 0;
    }

    /**
     * Checks if docker-compose has containers running that do not belong to this environment.
     *
     * @return bool
     */
    private function hasOtherRunningContainers(): bool
    {
        $status = $this->getStatus(true);
        foreach ($this->containers as $container) {
            unset($status[$container]);
        }
        $running = 0;
        foreach ($status as $name => $containerStatus) {
            if ($containerStatus === EnvironmentInterface::STATUS_STARTED) {
                $running++;
            }
        }

        return $running > 0;
    }

    private function hasContainerWithStatus(int $status): bool
    {
        $containerStatus = $this->getStatus();
        foreach ($this->containers as $container) {
            if ($containerStatus[$container] === $status) {
                return true;
            }
        }

        return false;
    }

    private function hasOnlyContainerWithStatus(int $status): bool
    {
        $containerStatus = $this->getStatus();
        foreach ($this->containers as $container) {
            if ($containerStatus[$container] !== $status) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function isStarted(bool $fully = false): bool
    {
        if ($fully) {
            return $this->hasOnlyContainerWithStatus(EnvironmentInterface::STATUS_STARTED);
        }

        return $this->hasContainerWithStatus(EnvironmentInterface::STATUS_STARTED);
    }

    /**
     * {@inheritDoc}
     */
    public function hasError(): bool
    {
        return $this->hasContainerWithStatus(EnvironmentInterface::STATUS_FAILED);
    }

    /**
     * {@inheritDoc}
     */
    public function canShell(): bool
    {
        return $this->shell !== null;
    }

    /**
     * {@inheritDoc}
     */
    public function run(string $command): Shell
    {
        if ($this->shell === null) {
            throw new RuntimeException('Shell is not supported on this environment.');
        }
        $status = $this->getStatus();
        if ($status[$this->shell] !== EnvironmentInterface::STATUS_STARTED) {
            throw new RuntimeException('The container for shell is not running.');
        }
        chdir($this->path);
        
        $shell = $this->getShell();
        $shell->passthru("docker-compose exec {$this->shell} {$command}");
        
        return $shell;
    }

    /**
     * {@inheritDoc}
     */
    public function getStatus(bool $full = false): array
    {
        if ($this->state === null) {
            $this->state = $this->getCurrentState();
        }

        $state = $this->state;

        if (!$full) {
            foreach (array_keys($state) as $key) {
                if (strpos($key, '_') === 0) {
                    unset($state[$key]);
                }
            }
        }

        return $state;
    }

    /**
     * {@inheritDoc}
     */
    public function build(): void
    {
        chdir($this->path);
        $this->getShell()->exec('docker-compose build --parallel');
    }

    /**
     * {@inheritDoc}
     */
    public function hasInitContainers(): bool
    {
        return count($this->initContainers) > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function runInitContainers(): void
    {
        chdir($this->path);
        $shell = $this->getShell();
        foreach ($this->initContainers as $initContainer) {
            if (is_string($initContainer)) {
                $container = $initContainer;
                $arguments = [];
            } else {
                $container = $initContainer['container'];
                $arguments = $initContainer['arguments'];
            }
            $containerArguments = is_array($arguments) ? implode(' ', $arguments) : $arguments;
            $shell->exec("docker-compose run {$container} {$containerArguments}");
        }
    }

    /**
     * Retrieve the docker compose configuration.
     *
     * @return array
     */
    private function getDockerComposeConfig(): array
    {
        if ($this->dockerComposeConfig === null) {
            $this->dockerComposeConfig = $this->loadDockerComposeConfig();
        }

        return $this->dockerComposeConfig;
    }

    /**
     * Load docker configuration file.
     *
     * @return array
     */
    private function loadDockerComposeConfig(): array
    {
        $dockerComposeFile = null;
        $filenames = ['docker-compose.yaml', 'docker-compose.yml'];
        foreach ($filenames as $filename) {
            $path = $this->path . DIRECTORY_SEPARATOR . $filename;
            if (file_exists($path)) {
                $dockerComposeFile = $path;
                break;
            }
        }

        if ($dockerComposeFile === null) {
            throw new RuntimeException('Docker-compose yaml file not found.');
        }

        $config = Yaml::parseFile($dockerComposeFile);

        if (!is_array($config)) {
            throw new RuntimeException('Docker compose configuration parse error.');
        }

        return $config;
    }

    /**
     * Retrieve the container name (prefix).
     *
     * @param string $container
     *
     * @return string
     */
    private function getContainerName(string $container): string
    {
        $serviceConfig = $this->getServiceConfig($container);

        return $serviceConfig['container_name'] ?? (basename($this->path) . '_' . $container);
    }

    /**
     * Retrieve service configuration.
     *
     * @param string $serviceName
     *
     * @return array|null
     */
    private function getServiceConfig(string $serviceName): ?array
    {
        $dockerComposeConfig = $this->getDockerComposeConfig();
        return $dockerComposeConfig['services'][$serviceName] ?? null;
    }

    /**
     * Determine the state of the containers.
     *
     * @return array
     */
    private function getCurrentState(): array
    {
        $state = [];
        foreach ($this->containers as $container) {
            $state[$container] = EnvironmentInterface::STATUS_STOPPED;
        }
        $shell = $this->getShell();
        chdir($this->path);
        $shell->exec('tput cols');
        $width = $shell->getOutput();
        $shell->exec("stty cols 200; docker-compose ps; stty cols {$width}");
        $lines = $shell->getOutputLines();
        $services = $this->outputParser->parsePS($lines);

        foreach ($services as $service) {
            $serviceState = EnvironmentInterface::STATUS_FAILED;
            if ($service->getState() === 'Up') {
                $serviceState = EnvironmentInterface::STATUS_STARTED;
            }
            if ($service->getState() === 'Exit 0') {
                $serviceState = EnvironmentInterface::STATUS_STOPPED;
            }
            $containerFullName = $service->getName();
            $selectedContainer = '';
            foreach ($this->containers as $container) {
                $containerName = $this->getContainerName($container);
                if ((strpos($containerFullName, $containerName) !== false) && (strlen($container) > strlen($selectedContainer))) {
                    $selectedContainer = $container;
                }
            }
            if ($selectedContainer !== '') {
                $state[$selectedContainer] = $serviceState;
            } else {
                $state['_' . $service->getName()] = $serviceState;
            }
        }

        return $state;
    }

    /**
     * Resets the known state, forcing getState to re-evaluate the current state.
     */
    public function resetState()
    {
        $this->state = null;

        if (!$this->resettingSisters) {
            $this->resettingSisters = true;
            foreach ($this->sisters as $sister) {
                $sister->resetState();
            }
            $this->resettingSisters = false;
        }
    }

    /**
     * @param EnvironmentInterface $environment
     */
    public function notify(EnvironmentInterface $environment): void
    {
        parent::notify($environment);
        if (($environment instanceof self) && $environment->getPath() === $this->getPath()) {
            $this->sisters[] = $environment;
        }
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }
}
