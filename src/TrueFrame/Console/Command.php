<?php

namespace TrueFrame\Console;

use TrueFrame\Application;
use TrueFrame\Config\Repository;
use TrueFrame\View\Compiler;
use TrueFrame\Database\Migrations\Migrator;
use TrueFrame\Routing\Router;
use TrueFrame\AI\ScaffolderInterface;

abstract class Command
{
    /**
     * The application instance.
     *
     * @var Application
     */
    protected Application $app;

    /**
     * The command's signature.
     *
     * @var string
     */
    protected string $signature;

    /**
     * The command's description.
     *
     * @var string
     */
    protected string $description;

    /**
     * The parsed arguments for the command.
     *
     * @var array
     */
    protected array $arguments = [];

    /**
     * Set the application instance.
     *
     * @param Application $app
     * @return void
     */
    public function setApplication(Application $app): void
    {
        $this->app = $app;
    }

    /**
     * Get the command's signature.
     *
     * @return string
     */
    public function getSignature(): string
    {
        return $this->signature;
    }

    /**
     * Get the command's description.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Run the command.
     *
     * @param array $args
     * @return int
     */
    public function run(array $args): int
    {
        $this->parseArguments($args);
        return $this->handle();
    }

    /**
     * Parse the command line arguments.
     *
     * @param array $args
     * @return void
     */
    protected function parseArguments(array $args): void
    {
        $this->arguments = [];
        foreach ($args as $arg) {
            if (str_starts_with($arg, '--')) {
                $parts = explode('=', substr($arg, 2), 2);
                $key = $parts[0];
                $value = $parts[1] ?? true;
                $this->arguments['--' . $key] = $value;
            } else {
                $this->arguments[] = $arg;
            }
        }
    }

    /**
     * Get an argument by its name or index.
     *
     * @param string|int|null $key
     * @param mixed $default
     * @return mixed
     */
    protected function argument(string|int $key = null, mixed $default = null): mixed
    {
        if (is_null($key)) {
            return $this->arguments;
        }
        return $this->arguments[$key] ?? $default;
    }

    /**
     * Get an option by its name.
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    protected function option(string $key = null, mixed $default = null): mixed
    {
        if (is_null($key)) {
            return array_filter($this->arguments, fn($k) => is_string($k) && str_starts_with($k, '--'), ARRAY_FILTER_USE_KEY);
        }
        $key = '--' . ltrim($key, '-'); // Ensure it starts with --
        return $this->arguments[$key] ?? $default;
    }

    /**
     * Write an info message to the console.
     *
     * @param string $message
     * @return void
     */
    protected function info(string $message): void
    {
        $this->app->make(Application::class)->info($message);
    }

    /**
     * Write a warning message to the console.
     *
     * @param string $message
     * @return void
     */
    protected function warn(string $message): void
    {
        $this->app->make(Application::class)->warn($message);
    }

    /**
     * Write an error message to the console.
     *
     * @param string $message
     * @return void
     */
    protected function error(string $message): void
    {
        $this->app->make(Application::class)->error($message);
    }

    /**
     * Write a regular message to the console.
     *
     * @param string $message
     * @return void
     */
    protected function line(string $message): void
    {
        $this->app->make(Application::class)->line($message);
    }

    /**
     * Get the config repository.
     *
     * @return Repository
     */
    protected function config(): Repository
    {
        return $this->app->make(Repository::class);
    }

    /**
     * Get the view compiler.
     *
     * @return Compiler
     */
    protected function compiler(): Compiler
    {
        return $this->app->make(Compiler::class);
    }

    /**
     * Get the migrator instance.
     *
     * @return Migrator
     */
    protected function migrator(): Migrator
    {
        return $this->app->make(Migrator::class);
    }

    /**
     * Get the router instance.
     *
     * @return Router
     */
    protected function router(): Router
    {
        return $this->app->make(Router::class);
    }

    /**
     * Get the AI scaffolder instance.
     *
     * @return ScaffolderInterface
     */
    protected function scaffolder(): ScaffolderInterface
    {
        return $this->app->make($this->config()->get('ai.scaffolder'));
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    abstract public function handle(): int;
}