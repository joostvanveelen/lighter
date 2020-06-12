<?php

namespace Lighter\Command\Environment;

use Lighter\Configuration;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class RemoveCommand extends Command
{
    /**
     * @var string|null The default command name
     */
    protected static $defaultName = 'environment:remove';

    /**
     * @var Configuration
     */
    private $configuration;

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
            ->setDescription('Remove environments.')
            ->setHelp('This command allows you to remove an environment. Note that the files of the environment will not be removed, just the reference to that environment.')
            ->addArgument('environments', InputArgument::IS_ARRAY);
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $environments = $input->getArgument('environments');
        if (count($environments) === 0) {
            $helper = $this->getHelper('question');
            $environmentsQuestion = new ChoiceQuestion(
                'Select environment(s) to remove (comma separate multiple options):',
                $this->configuration->getEnvironmentNames()
            );
            $environmentsQuestion->setMultiselect(true);
            $environments = $helper->ask($input, $output, $environmentsQuestion);
        }

        foreach ($environments as $environment) {
            $this->configuration->deleteEnvironment($environment);
        }

        $this->configuration->save();
    }
}
