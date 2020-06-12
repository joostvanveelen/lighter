<?php

namespace Lighter\Command;

use Lighter\Environment\EnvironmentManager;
use Lighter\Environment\EnvironmentInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Stop environments.
 *
 * @package Lighter\Command
 */
class StopCommand extends Command
{
    use StopEnvironmentTrait;

    /**
     * @var string|null The default command name
     */
    protected static $defaultName = 'stop';

    /**
     * @var EnvironmentManager
     */
    private $environmentManager;

    /**
     * StopCommand constructor.
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
            ->setDescription('Stops environments.')
            ->setHelp('This command allows you to stop environments')
            ->setAliases(['off', 'down', 'halt', 'armageddon'])
            ->addArgument('environments', InputArgument::IS_ARRAY);
    }

    /**
     * {@inheritDoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $environmentNames = $input->getArgument('environments');
        if (!$environmentNames) {
            $environmentNames = $this->environmentManager->getEnvironmentNames();
        }
        foreach ($environmentNames as $environmentName) {
            $environment = $this->environmentManager->getEnvironmentByName($environmentName);
            $this->stopEnvironment($this->environmentManager, $environment, $output);
        }
    }
}
