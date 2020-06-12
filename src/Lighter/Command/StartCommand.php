<?php

namespace Lighter\Command;

use Lighter\Environment\EnvironmentManager;
use Lighter\Environment\EnvironmentInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Starts environments.
 *
 * @package Lighter\Command
 */
class StartCommand extends Command
{
    use StartEnvironmentTrait;

    /**
     * @var string|null The default command name
     */
    protected static $defaultName = 'start';

    /**
     * @var EnvironmentManager
     */
    private $environmentManager;

    /**
     * StartCommand constructor.
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
            ->setDescription('Start environments.')
            ->setHelp('This command allows you to start environments')
            ->setAliases(['on', 'up'])
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
            $this->startEnvironment($this->environmentManager, $environment, $output);
        }
    }
}
