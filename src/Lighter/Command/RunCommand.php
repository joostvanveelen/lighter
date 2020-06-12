<?php


namespace Lighter\Command;


use Lighter\Environment\EnvironmentInterface;
use Lighter\Environment\EnvironmentManager;
use Lighter\Environment\ShellInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RunCommand extends Command
{
    /**
     * @var string|null The default command name
     */
    protected static $defaultName = 'run';

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
            ->setDescription('Run a command in an environment.')
            ->setHelp('This command allows you to run a command in an environment using the container indicated for shell execution.')
            ->addArgument('environment')
            ->addArgument('cmd', InputArgument::IS_ARRAY);
    }

    /**
     * {@inheritDoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $environmentName = $input->getArgument('environment');
        $environment = $this->environmentManager->getEnvironmentByName($environmentName);
        $command = implode(' ', $input->getArgument('cmd'));
        $this->runCommand($environment, $output, $command);
    }

    /**
     * Execute a shell in the given environment.
     *
     * @param EnvironmentInterface $environment
     * @param OutputInterface $output
     */
    private function runCommand(EnvironmentInterface $environment, OutputInterface $output, string $command): void
    {
        if (!($environment instanceof ShellInterface) || !$environment->canShell()) {
            $output->write($environment->getDescription() . ' does not support a shell.');
        }
        if (!$environment->isStarted()) {
            $output->write($environment->getDescription() . ' is not running.');
        }

        $environment->run($command);
    }
}
