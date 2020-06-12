<?php /** @noinspection DisconnectedForeachInstructionInspection */

namespace Lighter\Command\Environment;

use Lighter\Configuration;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ListCommand
 *
 * @package Lighter\Command\Environment
 */
class ListCommand extends Command
{
    /**
     * @var string|null The default command name
     */
    protected static $defaultName = 'environment:list';

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * ListCommand constructor.
     *
     * @param Configuration $configuration
     */
    public function __construct(Configuration $configuration)
    {
        parent::__construct();
        $this->configuration = $configuration;
    }

    /**
     * {@inheritDoc}
     */
    protected function configure(): void
    {
        $this
            ->setDescription('List environments.')
            ->setHelp('This command allows you to list the known environments.');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->configuration->getEnvironmentsConfig() as $environmentConfig) {
            $name = $environmentConfig['name'] ?? 'unknown';
            $description = $environmentConfig['description'] ?? '';
            unset($environmentConfig['name'], $environmentConfig['description']);
            $output->writeln("Environment '<comment>{$name}</comment>': {$description}");
            foreach ($environmentConfig as $var => $value) {
                if (is_array($value)) {
                    $value = '[ ' . implode(', ', $value) . ' ]';
                }
                $output->writeln("<info>{$var}</info>: {$value}");
            }
            $output->writeln('');
        }
    }
}
