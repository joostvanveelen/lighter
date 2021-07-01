<?php

namespace Lighter\Command;

use Lighter\Environment\EnvironmentInterface;
use Lighter\Environment\EnvironmentManager;
use RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;

trait StartEnvironmentTrait
{
    /**
     * Starts the given environment. Recursively starts it's dependencies
     *
     * @param EnvironmentManager   $environmentManager
     * @param EnvironmentInterface $environment
     * @param OutputInterface      $output
     *
     * @return EnvironmentInterface[] array of started environments in the order they were started
     *
     * @throws RuntimeException when an environment fails to start
     */
    private function startEnvironment(
        EnvironmentManager $environmentManager,
        EnvironmentInterface $environment,
        OutputInterface $output
    ): array {
        $startedEnvironments = [];

        if ($environment->isStarted(true)) {
            return $startedEnvironments;
        }

        foreach ($environment->getDependencies() as $dependencyEnvName) {
            $dependencyEnvironment = $environmentManager->getEnvironmentByName($dependencyEnvName);
            foreach ($this->startEnvironment($environmentManager, $dependencyEnvironment, $output) as $startedEnvironment) {
                $startedEnvironments[] = $startedEnvironment;
            }
        }

        $output->write('Starting ' . $environment->getDescription() . '...');
        $environment->start();
        if ($environment->hasError()) {
            $output->write('.');
            $environment->stop();
            $output->write('.');
            $environment->start();
            if ($environment->hasError()) {
                throw new RuntimeException("Environment {$environment->getName()} failed to start.");
            }
        }
        $startedEnvironments[] = $environment;
        $output->writeln(' <info>Done</info>');

        return $startedEnvironments;
    }
}
