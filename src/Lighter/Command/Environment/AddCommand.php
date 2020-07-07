<?php

namespace Lighter\Command\Environment;

use Lighter\Configuration;
use Lighter\Shell;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Yaml\Yaml;

/**
 * Adds a new environment to the configuration
 *
 * @package Lighter\Command\Environment
 */
class AddCommand extends Command
{
    /**
     * @var string|null The default command name
     */
    protected static $defaultName = 'environment:add';

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var Shell
     */
    private $shell;

    /**
     * AddCommand constructor.
     *
     * @param Configuration $configuration
     */
    public function __construct(Configuration $configuration, Shell $shell)
    {
        parent::__construct();
        $this->configuration = $configuration;
        $this->shell = $shell;
    }

    /**
     * {@inheritDoc}
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Add an environment.')
            ->setHelp('This command allows you to add a new environment.');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = [];

        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion('Environment type [docker-compose]?', [
            'network',
            'traefik',
            'docker-compose'
        ], 'docker-compose');
        $config['type'] = $helper->ask($input, $output, $question);

        $config = $this->getSharedInfo($input, $output, $config);

        switch ($config['type']) {
            case 'network':
                $config = $this->getNetworkInfo($input, $output, $config);
                break;
            case 'docker-compose':
                $config = $this->getDockerComposeInfo($input, $output, $config);
                break;
        }

        $this->configuration->addEnvironment($config);
        $this->configuration->save();
    }

    /**
     * Ask the information that is shared for each environment: name, description and dependencies.

     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param array           $config
     *
     * @return array
     */
    protected function getSharedInfo(InputInterface $input, OutputInterface $output, array $config): array
    {
        $helper = $this->getHelper('question');

        $name = new Question('Environment name (no whitespace): ');
        $name->setValidator(static function ($answer) {
            if (preg_match('/\\s/', $answer)) {
                throw new RuntimeException('The environment name cannot contain whitespace.');
            }

            return $answer;
        });
        $config['name'] = $helper->ask($input, $output, $name);

        $description = new Question('Environment description: ');
        $config['description'] = $helper->ask($input, $output, $description);

        $names = $this->configuration->getEnvironmentNames();
        if ($names) {
            $dependencies = new ChoiceQuestion(
                'Environment dependencies (comma separate multiple options):',
                array_merge(['[none]'], $names)
            );
            $dependencies->setMultiselect(true);
            $config['dependencies'] = $helper->ask($input, $output, $dependencies);
            $noneIndex = array_search('[none]', $config['dependencies']);
            if ($noneIndex !== false) {
                unset($config['dependencies'][$noneIndex]);
            }
        } else {
            $config['dependencies'] = [];
        }

        return $config;
    }

    /**
     * Ask the extra information needed for network type environments.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param array           $config
     *
     * @return array
     */
    private function getNetworkInfo(InputInterface $input, OutputInterface $output, array $config): array
    {
        $helper = $this->getHelper('question');

        $networkName = new Question('Network name: ');
        $config['networkName'] = $helper->ask($input, $output, $networkName);

        return $config;
    }

    /**
     * Ask the extra information needed for docker-compose type environments.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param array           $config
     *
     * @return array
     */
    private function getDockerComposeInfo(InputInterface $input, OutputInterface $output, array $config): array
    {
        $helper = $this->getHelper('question');

        $path = null;
        do {
            $pathQuestion = new Question('Environment path: ');
            $pathQuestion->setAutocompleterCallback($this->getPathAutocompleterCallback());
            $givenPath = $helper->ask($input, $output, $pathQuestion);
            try {
                $path = $this->validatePath($givenPath);
                $services = $this->getDockerComposeServices($path);
            } catch (RuntimeException $e) {
                $path = null;
                $output->writeln('<error>' . $e->getMessage() . '</error>');
            }
        }
        while ($path === null);

        $config['path'] = $path;

        $containers = new ChoiceQuestion(
            'Which containers need to be started (comma separate multiple options):',
            array_merge(['[all]'], $services)
        );
        $containers->setMultiselect(true);
        $config['containers'] = $helper->ask($input, $output, $containers);
        if (in_array('[all]', $config['containers'], true)) {
            $config['containers'] = $services;
        }

        $config['initContainers'] = [];
        do {
            $initContainer = null;
            $initContainerQuestion = new ChoiceQuestion(
                'Add init container (containers that are run after a rebuild to initialize the project):',
                array_merge(['[skip/continue]'], $services)
            );
            $initContainerName = $helper->ask($input, $output, $initContainerQuestion);
            if ($initContainerName !== '[skip/continue]') {
                $argumentsQuestion = new Question('Init container arguments:');
                $arguments = $helper->ask($input, $output, $argumentsQuestion);
                $initContainer = [
                    'container' => $initContainerName,
                    'arguments' => $arguments,
                ];
                $config['initContainers'][] = $initContainer;
            }
        } while ($initContainer !== null);

        return $config;
    }

    /**
     * Construct an autocomplete callback for filepath questions.
     *
     * @return callable
     */
    private function getPathAutocompleterCallback(): callable
    {
        return static function (string $userInput): array {
            // Strip any characters from the last slash to the end of the string
            // to keep only the last directory and generate suggestions for it
            $inputPath = preg_replace('%(/|^)[^/]*$%', '$1', $userInput);
            $inputPath = '' === $inputPath ? '.' : $inputPath;

            // CAUTION - this example code allows unrestricted access to the
            // entire filesystem. In real applications, restrict the directories
            // where files and dirs can be found
            $foundFilesAndDirs = @scandir($inputPath) ?: [];

            return array_map(static function ($dirOrFile) use ($inputPath) {
                return $inputPath.$dirOrFile;
            }, $foundFilesAndDirs);
        };
    }

    /**
     * Retrieve the services defined in the dock-compose configuration
     *
     * @param string $path path to the docker-compose file
     *
     * @return string[]
     */
    private function getDockerComposeServices(string $path): array
    {
        chdir($path);
        if ($this->shell->exec('docker-compose config') !== 0) {
            throw new RuntimeException('Error while retrieving docker-compose configuration: ' . $this->shell->getOutput());
        }
        $dockerCompose = Yaml::parse($this->shell->getOutput());
        $services = $dockerCompose['services'] ?? [];

        return array_keys($services);
    }

    /**
     * Validate a given path.
     *
     * @param string $path
     *
     * @return string The full absolute pathname
     *
     * @throws RuntimeException when the path cannot be resolved.
     */
    private function validatePath(string $path): string
    {
        if (strpos($path, '/') !== 0) {
            //relative path

            //check within current working directory:
            $cwd = getcwd();
            if (file_exists($cwd . '/' . $path) && is_dir($cwd . '/' . $path)) {
                return realpath($cwd . '/' . $path);
            }

            //check within user's home:
            $home = getenv('HOME');
            if (file_exists($home . '/' . $path) && is_dir($home . '/' . $path)) {
                return realpath($home . '/' . $path);
            }

            throw new RuntimeException("Path '{$path}' does not exist.");
        }

        //absolute path
        if (!file_exists($path) || !is_dir($path)) {
            throw new RuntimeException("Path '{$path}' does not exist.");
        }

        return realpath($path);
    }
}
