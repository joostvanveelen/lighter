<?php

namespace Lighter\Command;

use Lighter\Environment\EnvironmentManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Restart environments
 *
 * @package Lighter\Command
 */
class RestartCommand extends Command
{
    use RestartEnvironmentTrait;

    /**
     * @var string|null The default command name
     */
    protected static $defaultName = 'restart';

    /**
     * @var EnvironmentManager
     */
    private $environmentManager;

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
            ->setDescription('Restart environments.')
            ->setHelp('This command allows you to restart already running environments')
            ->setAliases(['reload'])
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
            $this->restartEnvironment($this->environmentManager, $environment, $output);
        }
    }
}
