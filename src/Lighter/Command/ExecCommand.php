<?php

namespace Lighter\Command;

use Lighter\Environment\EnvironmentInterface;
use Lighter\Environment\EnvironmentManager;
use Lighter\Environment\ShellInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Execute a shell in the (running) environment.
 *
 * @package Lighter\Command
 */
class ExecCommand extends Command
{
    /**
     * @var string|null The default command name
     */
    protected static $defaultName = 'exec';

    /**
     * @var EnvironmentManager
     */
    private $environmentManager;

    /**
     * ExecCommand constructor.
     *
     * @param EnvironmentManager $environmentManager
     */
    public function __construct(EnvironmentManager $environmentManager)
    {
        parent::__construct();
        $this->environmentManager = $environmentManager;
    }

    /**
     * {@inheritDoc}
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Start a shell in an environment.')
            ->setHelp('This command allows you to start a shell in an environment and execute command inside the container.')
            ->addArgument('environment');
    }

    /**
     * {@inheritDoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $environmentName = $input->getArgument('environment');
        $environment = $this->environmentManager->getEnvironmentByName($environmentName);
        $this->exec($environment, $output);
    }

    /**
     * Execute a shell in the given environment.
     *
     * @param EnvironmentInterface $environment
     * @param OutputInterface $output
     */
    private function exec(EnvironmentInterface $environment, OutputInterface $output): void
    {
        if (!($environment instanceof ShellInterface) || !$environment->canShell()) {
            $output->write($environment->getDescription() . ' does not support a shell.');
        }
        if (!$environment->isStarted()) {
            $output->write($environment->getDescription() . ' is not running.');
        }

        $environment->run('bash');
    }
}
