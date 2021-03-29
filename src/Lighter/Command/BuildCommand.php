<?php

namespace Lighter\Command;

use Lighter\Environment\BuildInterface;
use Lighter\Environment\EnvironmentManager;
use Lighter\Environment\EnvironmentInterface;
use Lighter\Environment\InitInterface;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Build environments.
 *
 * @package Lighter\Command
 */
class BuildCommand extends Command
{
    use RestartEnvironmentTrait;

    /**
     * @var string|null The default command name
     */
    protected static $defaultName = 'build';

    /**
     * @var EnvironmentManager
     */
    private $environmentManager;

    /**
     * BuildCommand constructor.
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
            ->setDescription('Build environments.')
            ->setHelp('This command allows you to (re)build environments')
            ->setAliases(['rebuild'])
            ->addArgument('environments', InputArgument::IS_ARRAY)
            ->addOption('restart', 'r', InputOption::VALUE_NONE, 'Restart the environments if they were running.')
            ->addOption('skip-init', 'i', InputOption::VALUE_NONE, 'Skip running the init containers when restarting.');
    }

    /**
     * {@inheritDoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getArgument('command') === 'rebuild') {
            $input->setOption('restart', true);
        }
        $environmentNames = $input->getArgument('environments');
        if (!$environmentNames) {
            $environmentNames = $this->environmentManager->getEnvironmentNames();
        }
        foreach ($environmentNames as $environmentName) {
            $environment = $this->environmentManager->getEnvironmentByName($environmentName);
            $this->buildEnvironment(
                $environment,
                $output,
                $input->getOption('restart'),
                $input->getOption('skip-init')
            );
        }
    }

    /**
     * Restarts the given environment. Note that environments depending on the restarted environment are NOT restarted.
     *
     * @param EnvironmentInterface $environment
     * @param OutputInterface      $output
     * @param bool                 $restart
     * @param bool                 $skipInit
     */
    private function buildEnvironment(
        EnvironmentInterface $environment,
        OutputInterface $output,
        bool $restart,
        bool $skipInit
    ): void {
        if (!($environment instanceof BuildInterface)) {
            return;
        }

        $output->write('Building ' . $environment->getDescription() . '...');
        if (!$environment->build()) {
            throw new RuntimeException("Environment {$environment->getName()} failed to build.");
        }
        $output->writeln(' <info>Done</info>');

        if ($restart && $environment->isStarted()) {
            $this->restartEnvironment($this->environmentManager, $environment, $output);
            if ($environment instanceof InitInterface && !$skipInit && $environment->hasInitContainers()) {
                $output->write('Running initialisation for ' . $environment->getDescription() . '...');
                $environment->runInitContainers();
                $output->writeln(' <info>Done</info>');
            }
        }
    }
}
