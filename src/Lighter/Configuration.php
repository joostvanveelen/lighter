<?php

namespace Lighter;

use Symfony\Component\Yaml\Yaml;

/**
 * Makes the content of the configuration file available to the application for reading and modifying.
 *
 * @package Lighter
 */
class Configuration
{
    /**
     * @var string
     */
    private $configurationFile;

    /**
     * @var array
     */
    private $configuration;

    /**
     * Configuration constructor.
     *
     * @param string $configurationFile
     */
    public function __construct(string $configurationFile)
    {
        $this->configurationFile = $configurationFile;
    }

    /**
     * @return array
     */
    public function getSelfUpdateConfig(): array
    {
        $this->load();

        return array_key_exists('self-update', $this->configuration) ? $this->configuration['self-update'] : [];
    }

    /**
     * @param array $config
     */
    public function setSelfUpdateConfig(array $config): void
    {
        $this->load();

        $this->configuration['self-update'] = $config;
    }

    /**
     * @return array
     */
    public function getShellConfig(): array
    {
        $this->load();

        return array_key_exists('shell', $this->configuration) ? $this->configuration['shell'] : [];
    }

    /**
     * @param array $config
     */
    public function setShellConfig(array $config)
    {
        $this->load();

        $this->configuration['shell'] = $config;
    }

    /**
     * @return array
     */
    public function getEnvironmentsConfig(): array
    {
        $this->load();

        return $this->configuration['environments'];
    }

    /**
     * Retrieve the names of all environments.
     *
     * @return array
     */
    public function getEnvironmentNames(): array
    {
        $names = [];
        foreach ($this->configuration['environments'] as $environmentConfig) {
            $names[] = $environmentConfig['name'];
        }

        return $names;
    }

    /**
     * Add an new environment.
     *
     * @param array $config
     */
    public function addEnvironment(array $config): void
    {
        $this->load();
        $this->configuration['environments'][] = $config;
    }

    /**
     * Delete an existing environment from the configuration. The actual environment will not be deleted.
     *
     * @param string $name
     */
    public function deleteEnvironment(string $name): void
    {
        $this->load();

        foreach ($this->configuration['environments'] as $key => $config) {
            if ($config['name'] == $name) {
                unset($this->configuration['environments'][$key]);
            }
        }
    }

    /**
     * Save the environment configuration to disk.
     */
    public function save(): void
    {
        file_put_contents($this->configurationFile, Yaml::dump($this->configuration, 16, 2));
    }

    /**
     * Returns true when the configuration has been loaded.
     *
     * @return bool
     */
    private function isLoaded(): bool
    {
        return is_array($this->configuration);
    }

    /**
     * Loads the configuration from disk.
     */
    private function load(): void
    {
        if (!$this->isLoaded()) {
            if (file_exists($this->configurationFile)) {
                $this->configuration = Yaml::parseFile($this->configurationFile);
            } else {
                $this->configuration = [];
            }
        }

        $this->configuration['environments'] = $this->configuration['environments'] ?? [];
    }
}