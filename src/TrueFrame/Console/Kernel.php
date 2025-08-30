<?php

namespace TrueFrame\Console;

use Exception;
use TrueFrame\Application;

class Kernel
{
    /**
     * @var Application The application instance.
     */
    protected Application $app;

    /**
     * @var array The commands provided by the framework.
     */
    protected array $commands = [
        Commands\ServeCommand::class,
        Commands\KeyGenerateCommand::class,
        Commands\CacheClearCommand::class,
        // Add more framework commands here
    ];

    /**
     * Kernel constructor.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Handle the incoming console command.
     *
     * @param array $argv The command-line arguments.
     * @return int The exit code.
     */
    public function handle(array $argv): int
    {
        // Remove the script name (trueframe) from argv
        array_shift($argv);

        $commandName = array_shift($argv); // The actual command, e.g., 'serve'

        if (!$commandName) {
            $this->listCommands();
            return 0;
        }

        foreach ($this->commands as $commandClass) {
            /** @var Command $command */
            $command = $this->app->resolve($commandClass); // Resolve command from container
            if ($command->getName() === $commandName) {
                try {
                    $command->execute($argv);
                    return 0; // Success
                } catch (Exception $e) {
                    $this->error("Error: " . $e->getMessage());
                    return 1; // Error
                }
            }
        }

        $this->error("Command '{$commandName}' not found.");
        $this->listCommands();
        return 1; // Command not found
    }

    /**
     * List all available commands.
     */
    protected function listCommands(): void
    {
        $this->info("TrueFrame Console Commands:");
        foreach ($this->commands as $commandClass) {
            /** @var Command $command */
            $command = $this->app->resolve($commandClass);
            $this->line("  {$command->getName()} - {$command->getDescription()}");
        }
    }

    /**
     * Output a line to the console.
     * @param string $message
     */
    public function line(string $message): void
    {
        echo $message . PHP_EOL;
    }

    /**
     * Output an info message to the console.
     * @param string $message
     */
    public function info(string $message): void
    {
        $this->line("\033[32m{$message}\033[0m"); // Green text
    }

    /**
     * Output an error message to the console.
     * @param string $message
     */
    public function error(string $message): void
    {
        $this->line("\033[31m{$message}\033[0m"); // Red text
    }
}