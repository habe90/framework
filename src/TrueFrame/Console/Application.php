<?php

namespace TrueFrame\Console;

use TrueFrame\Application as App;
use TrueFrame\Console\Command;
use TrueFrame\Console\Commands\AIScaffoldCommand;
use TrueFrame\Console\Commands\CacheClearCommand;
use TrueFrame\Console\Commands\DbSeedCommand;
use TrueFrame\Console\Commands\KeyGenerateCommand;
use TrueFrame\Console\Commands\MakeControllerCommand;
use TrueFrame\Console\Commands\MakeMiddlewareCommand;
use TrueFrame\Console\Commands\MakeMigrationCommand;
use TrueFrame\Console\Commands\MakeModelCommand;
use TrueFrame\Console\Commands\MakeProviderCommand;
use TrueFrame\Console\Commands\MakeRequestCommand;
use TrueFrame\Console\Commands\MakeSeederCommand;
use TrueFrame\Console\Commands\MigrateCommand;
use TrueFrame\Console\Commands\MigrateRollbackCommand;
use TrueFrame\Console\Commands\OptimizeCommand;
use TrueFrame\Console\Commands\RouteListCommand;
use TrueFrame\Console\Commands\ServeCommand;
use TrueFrame\Console\Commands\UiInstallCommand;

class Application
{
    /**
     * The TrueFrame application instance.
     *
     * @var App
     */
    protected App $app;

    /**
     * All of the registered commands.
     *
     * @var array<string, Command>
     */
    protected array $commands = [];

    /**
     * Create a new console application instance.
     *
     * @param App $app
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->registerCommands();
    }

    /**
     * Register the default commands.
     *
     * @return void
     */
    protected function registerCommands(): void
    {
        $this->add(new CacheClearCommand());
        $this->add(new DbSeedCommand());
        $this->add(new KeyGenerateCommand());
        $this->add(new MakeControllerCommand());
        $this->add(new MakeMiddlewareCommand());
        $this->add(new MakeMigrationCommand());
        $this->add(new MakeModelCommand());
        $this->add(new MakeProviderCommand());
        $this->add(new MakeRequestCommand());
        $this->add(new MakeSeederCommand());
        $this->add(new MigrateCommand());
        $this->add(new MigrateRollbackCommand());
        $this->add(new RouteListCommand());
        $this->add(new UiInstallCommand());
        $this->add(new AIScaffoldCommand());
        $this->add(new ServeCommand());
        $this->add(new OptimizeCommand());
    }

    /**
     * Add a command to the console application.
     *
     * @param Command $command
     * @return void
     */
    public function add(Command $command): void
    {
        $command->setApplication($this->app);
        $this->commands[$command->getSignature()] = $command;
    }

    /**
     * Run the console application.
     *
     * @param array $argv
     * @return int
     */
    public function run(array $argv): int
    {
        $commandName = $argv[0] ?? 'help';
        $args = array_slice($argv, 1);

        if ($commandName === 'help' || !isset($this->commands[$commandName])) {
            $this->displayHelp();
            return 0;
        }

        $command = $this->commands[$commandName];

        try {
            return $command->run($args);
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Display the help message and list all commands.
     *
     * @return void
     */
    protected function displayHelp(): void
    {
        echo "TrueFrame CLI (Version " . $this->app->version() . ")\n";
        echo "Usage: trueframe <command> [arguments]\n\n";
        echo "Available commands:\n";
        foreach ($this->commands as $signature => $command) {
            echo sprintf("  %-25s %s\n", $signature, $command->getDescription());
        }
    }

    /**
     * Write an info message to the console.
     *
     * @param string $message
     * @return void
     */
    public function info(string $message): void
    {
        echo "\033[32m" . $message . "\033[0m\n";
    }

    /**
     * Write a warning message to the console.
     *
     * @param string $message
     * @return void
     */
    public function warn(string $message): void
    {
        echo "\033[33m" . $message . "\033[0m\n";
    }

    /**
     * Write an error message to the console.
     *
     * @param string $message
     * @return void
     */
    public function error(string $message): void
    {
        echo "\033[31m" . $message . "\033[0m\n";
    }

    /**
     * Write a regular message to the console.
     *
     * @param string $message
     * @return void
     */
    public function line(string $message): void
    {
        echo $message . "\n";
    }
}