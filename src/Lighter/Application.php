<?php

namespace Lighter;

use Lighter\Command\BuildCommand;
use Lighter\Command\Environment\AddCommand;
use Lighter\Command\Environment\ListCommand;
use Lighter\Command\Environment\RemoveCommand;
use Lighter\Command\RestartCommand;
use Lighter\Command\Self\UpdateCommand;
use Lighter\Command\StatusCommand;
use Lighter\Command\StartCommand;
use Lighter\Command\StopCommand;
use Lighter\Environment\EnvironmentFactory;
use Lighter\Environment\EnvironmentManager;
use Symfony\Component\Console\Application as ConsoleApplication;

/**
 * Class Application
 *
 * @package Lighter
 */
class Application extends ConsoleApplication
{
    /**
     * Configure the application by loading the configuration and registering the commands
     */
    public function configure(): void
    {
        $home = getenv('HOME');
        $configurationFile = "{$home}/.lighter.yaml";
        $configuration = new Configuration($configurationFile);
        $shell = new Shell($configuration->getShellConfig());

        $environmentManager = new EnvironmentManager();
        $environmentsConfig = $configuration->getEnvironmentsConfig();
        foreach ($environmentsConfig as $environmentConfig) {
            $environmentManager->addEnvironment(EnvironmentFactory::buildEnvironment($shell, $environmentConfig));
        }

        //commands to manage the environment status
        $this->add(new BuildCommand($environmentManager));
        $this->add(new StartCommand($environmentManager));
        $this->add(new StatusCommand($environmentManager));
        $this->add(new RestartCommand($environmentManager));
        $this->add(new StopCommand($environmentManager));

        //commands to manage the environment config
        $this->add(new AddCommand($configuration, $shell));
        $this->add(new ListCommand($configuration));
        $this->add(new RemoveCommand($configuration));

        //other commands
        $this->add(new UpdateCommand($configuration, $shell));
    }
}
