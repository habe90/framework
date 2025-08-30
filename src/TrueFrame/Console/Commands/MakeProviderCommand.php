<?php

namespace TrueFrame\Console\Commands;

use TrueFrame\Console\Command;
use RuntimeException;

class MakeProviderCommand extends Command
{
    protected string $signature = 'make:provider';
    protected string $description = 'Create a new service provider class.';

    public function handle(): int
    {
        $name = $this->argument(0);

        if (empty($name)) {
            $this->error('Please provide a provider name.');
            return 1;
        }

        $content = file_get_contents(__DIR__ . "/stubs/provider.stub");

        $namespace = 'App\\Providers';
        $className = str_replace('.php', '', ucfirst($name));

        $content = str_replace('{{ namespace }}', $namespace, $content);
        $content = str_replace('{{ class }}', $className, $content);

        $path = $this->app->basePath("app/Providers/{$className}.php");

        if (file_exists($path)) {
            $this->error("Provider [{$className}] already exists!");
            return 1;
        }

        file_put_contents($path, $content);

        $this->info("Provider [{$className}] created successfully.");
        return 0;
    }
}