<?php

namespace Lighter\Command;

use Lighter\Environment\EnvironmentInterface;
use Lighter\Environment\EnvironmentManager;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Trait RestartEnvironmentTrait
 *
 * @package Lighter\Command
 */
trait RestartEnvironmentTrait
{
    use StartEnvironmentTrait, StopEnvironmentTrait;

    /**
     * Restart an environment. Note that environments depending on the restarted environment are also restarted.
     *
     * @param EnvironmentManager   $environmentManager
     * @param EnvironmentInterface $environment
     * @param OutputInterface      $output
     */
    private function restartEnvironment(
        EnvironmentManager $environmentManager,
        EnvironmentInterface $environment,
        OutputInterface $output
    ): void {
        if (!$environment->isStarted()) {
            $output->writeln($environment->getDescription() . ' is not running.');
            return;
        }

        $stoppedEnvironments = $this->stopEnvironment($environmentManager, $environment, $output);
        foreach (array_reverse($stoppedEnvironments) as $stoppedEnvironment) {
            $this->startEnvironment($environmentManager, $stoppedEnvironment, $output);
        }
    }
}

