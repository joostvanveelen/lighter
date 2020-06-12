<?php /** @noinspection DisconnectedForeachInstructionInspection */

namespace Lighter\Command;

use Lighter\Environment\EnvironmentManager;
use Lighter\Environment\EnvironmentInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Retrieve environment status.
 *
 * @package Lighter\Command
 */
class StatusCommand extends Command
{
    /**
     * @var string|null The default command name
     */
    protected static $defaultName = 'status';

    /**
     * @var EnvironmentManager
     */
    private $environmentManager;

    /**
     * StatusCommand constructor.
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
            ->setDescription('Get environment status.')
            ->setHelp('Display the status of each container within the environments.')
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
            $output->writeln("<comment>{$environment->getDescription()}</comment> ({$environment->getName()}): ");
            foreach ($environment->getStatus() as $container => $status) {
                $output->write("{$container}: ");
                switch ($status) {
                    case EnvironmentInterface::STATUS_STOPPED:
                        $output->writeln('stopped');
                        break;
                    case EnvironmentInterface::STATUS_STARTED:
                        $output->writeln('<info>started</info>');
                        break;
                    case EnvironmentInterface::STATUS_FAILED:
                        $output->writeln('<error>failed</error>');
                }
            }
            $output->writeln('');
        }
    }
}
