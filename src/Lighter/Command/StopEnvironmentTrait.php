<?php

namespace Lighter\Command;

use Lighter\Environment\EnvironmentInterface;
use Lighter\Environment\EnvironmentManager;
use Symfony\Component\Console\Output\OutputInterface;

trait StopEnvironmentTrait
{
    /**
     * Stops an environment. Recursively stops environments depending on the current one.
     *
     * @param EnvironmentManager   $environmentManager
     * @param EnvironmentInterface $environment
     * @param OutputInterface      $output
     *
     * @return EnvironmentInterface[] array of stopped environments in the order they were stopped
     */
    private function stopEnvironment(
        EnvironmentManager $environmentManager,
        EnvironmentInterface $environment,
        OutputInterface $output
    ): array {
        $stoppedEnvironments = [];

        if (!$environment->isStarted()) {
            return $stoppedEnvironments;
        }

        foreach ($environmentManager->getReverseDependencies($environment) as $dependencyEnvironment) {
            foreach ($this->stopEnvironment($environmentManager, $dependencyEnvironment, $output) as $stoppedEnvironment) {
                $stoppedEnvironments[] = $stoppedEnvironment;
            }
        }

        $output->write('Stopping ' . $environment->getDescription() . '...');
        $environment->stop();
        $stoppedEnvironments[] = $environment;
        $output->writeln(' <info>Done</info>');

        return $stoppedEnvironments;
    }
}
