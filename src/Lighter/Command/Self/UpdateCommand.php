<?php

namespace Lighter\Command\Self;

use Lighter\Configuration;
use Lighter\ShellTrait;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class UpdateCommand
 * @package Lighter\Command\Self
 */
class UpdateCommand extends Command
{
    use ShellTrait;

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var string|null The default command name
     */
    protected static $defaultName = 'self:update';

    /**
     * SelfUpdateCommand constructor.
     * @param Configuration $configuration
     */
    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
        parent::__construct();
    }

    /**
     * {@inheritDoc}
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Self-update Lighter to the latest release.')
            ->setHelp('This command allows you to easily update lighter.')
            ->addArgument(
                'branch',
                InputArgument::OPTIONAL,
                'Select the branch to get updates from.',
                $this->getConfigValue('branch', 'master')
            )
            ->addOption(
                'repository',
                'r',
                InputOption::VALUE_REQUIRED,
                'Set the repository to get updates from.',
                $this->getConfigValue('repository', 'git@github.com:joostvanveelen/lighter.git')
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Force updating, even when the latest version is already installed.'
            );
    }

    /**
     * {@inheritDoc}
     */
    public function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $repository = $input->getOption('repository');
        $branch = $input->getArgument('branch');

        $this->setConfigValue('repository', $repository);
        $this->setConfigValue('branch', $branch);

        $currentCommitHash = $this->getConfigValue('commitHash');
        $latestCommitHash = $this->getCommitHash($repository, $branch);
        if ($latestCommitHash === null) {
            $output->writeln("The branch {$branch} does not exist.");
            return 1;
        }
        if ($latestCommitHash !== $currentCommitHash || $input->getOption('force')) {
            $tempDir = $this->build($output, $repository, $latestCommitHash);
            $this->setConfigValue('commitHash', $latestCommitHash);
            $this->configuration->save();
            $this->selfUpdate($output, $tempDir);
        } else {
            $output->writeln('Already up-to-date!');
        }

        return 0;
    }

    /**
     * Retrieve the latest commit hash for the given repository and branch.
     *
     * @param $repository
     * @param $branch
     *
     * @return string|null
     */
    private function getCommitHash($repository, $branch): ?string
    {
        $shell = $this->getShell();
        $shell->exec("git ls-remote {$repository}");
        foreach ($shell->getOutputLines() as $line) {
            [$commitHash, $ref] = preg_split('/\s+/', $line);
            if ($ref === "refs/heads/{$branch}" || $ref === "refs/tags/{$branch}") {
                return $commitHash;
            }
        }

        return null;
    }

    /**
     * Build a new lighter and return the temporary folder where the new build is done.
     *
     * @param OutputInterface $output
     * @param string          $repository
     * @param string          $commitHash
     *
     * @return string
     */
    private function build(OutputInterface $output, string $repository, string $commitHash): string
    {
        $shell = $this->getShell();
        $tempDir = sys_get_temp_dir() . '/lighter_' . substr(md5(microtime()), 0, 8);
        if (file_exists($tempDir)) {
            throw new RuntimeException("Directory '{$tempDir}' already exists");
        }
        if (!mkdir($tempDir) && !is_dir($tempDir)) {
            throw new RuntimeException("Directory '{$tempDir}' was not created");
        }

        $output->write('Cloning Lighter repository...');
        $shell->exec("cd {$tempDir} && git clone -q {$repository} . && git checkout {$commitHash}");
        if ($shell->getStatus() !== 0) {
            throw new RuntimeException($shell->getOutput());
        }
        $output->writeln(' Done');

        $output->write('Building Lighter...');
        $shell->exec("cd {$tempDir} && php build.php");
        if ($shell->getStatus() !== 0) {
            throw new RuntimeException($shell->getOutput());
        }
        $output->writeln(' Done');

        return $tempDir;
    }

    /**
     * Overwrite the existing lighter phar and cleanup the temp dir.
     *
     * @param OutputInterface $output
     * @param string          $tempDir
     *
     * @throws RuntimeException
     */
    private function selfUpdate(OutputInterface $output, string $tempDir): void
    {
        $shell = $this->getShell();
        $targetFile = realpath($_SERVER['SCRIPT_FILENAME']);
        $output->write('Installing Lighter...');
        if (substr($targetFile, -4) === '.php') {
            $output->writeln(' Skipped overwriting a .php file');
        } else {
            if (!unlink($targetFile) || !rename("{$tempDir}/build/lighter.phar", $targetFile)) {
                throw new RuntimeException("Failed installing lighter to {$targetFile}");
            }
            $output->writeln(' Done');
        }

        $output->write('Cleaning up...');
        $shell->exec("rm -rf {$tempDir}");
        if ($shell->getStatus() !== 0) {
            throw new RuntimeException($shell->getOutput());
        }
        $output->writeln(' Done');
    }

    /**
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    private function getConfigValue(string $key, $default = null)
    {
        $config = $this->configuration->getSelfUpdateConfig();
        return array_key_exists($key, $config) ? $config[$key] : $default;
    }

    /**
     * @param string $key
     * @param mixed  $value
     */
    private function setConfigValue(string $key, $value): void
    {
        $config = $this->configuration->getSelfUpdateConfig();
        if (!array_key_exists($key, $config) || $config[$key] !== $value) {
            $config[$key] = $value;
            $this->configuration->setSelfUpdateConfig($config);
        }
    }
}
