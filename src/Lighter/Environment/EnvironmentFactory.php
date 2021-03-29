<?php

namespace Lighter\Environment;

use Lighter\Environment\Type\DockerCompose;
use Lighter\Environment\Type\Network;
use Lighter\Environment\Type\Traefik;
use Lighter\Shell;
use RuntimeException;

/**
 * Factory to build environments
 */
class EnvironmentFactory
{
    /**
     * @param Shell $shell
     * @param array $config
     *
     * @return DockerCompose|Network|Traefik
     *
     * @throws RuntimeException
     */
    public static function buildEnvironment(Shell $shell, array $config)
    {
        $type = $config['type'] ?? '';
        switch (strtolower($type)) {
            case 'network':
                return new Network($shell, $config);
            case 'traefik':
                return new Traefik($shell, $config);
            case 'docker-compose':
                return new DockerCompose($shell, $config);
            default:
                throw new RuntimeException("Unknown environment type: '{$type}'.");
        }
    }
}
