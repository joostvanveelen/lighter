<?php

namespace Lighter\Environment;

use Lighter\Environment\Type\DockerCompose;
use Lighter\Environment\Type\Network;
use Lighter\Environment\Type\Traefik;
use RuntimeException;

/**
 * Factory to build environments
 */
class EnvironmentFactory
{
    /**
     * @param array $config
     *
     * @return DockerCompose|Network|Traefik
     *
     * @throws RuntimeException
     */
    public static function buildEnvironment(array $config)
    {
        $type = $config['type'] ?? '';
        switch (strtolower($type)) {
            case 'network':
                return new Network($config);
            case 'traefik':
                return new Traefik($config);
            case 'docker-compose':
                return new DockerCompose($config);
            default:
                throw new RuntimeException("Unknown environment type: '{$type}'.");
        }
    }
}
