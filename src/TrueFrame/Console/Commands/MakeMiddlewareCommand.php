<?php

namespace TrueFrame\Console\Commands;

use TrueFrame\Console\Command;
use RuntimeException;

class MakeMiddlewareCommand extends Command
{
    protected string $signature = 'make:middleware';
    protected string $description = 'Create a new middleware class.';

    public function handle(): int
    {
        $name = $this->argument(0);

        if (empty($name)) {
            $this->error('Please provide a middleware name.');
            return 1;
        }

        $content = file_get_contents(__DIR__ . "/stubs/middleware.stub");

        $namespace = 'App\\Http\\Middleware';
        $className = str_replace('.php', '', ucfirst($name));

        $content = str_replace('{{ namespace }}', $namespace, $content);
        $content = str_replace('{{ class }}', $className, $content);

        $path = $this->app->basePath("app/Http/Middleware/{$className}.php");

        if (file_exists($path)) {
            $this->error("Middleware [{$className}] already exists!");
            return 1;
        }

        file_put_contents($path, $content);

        $this->info("Middleware [{$className}] created successfully.");
        return 0;
    }
}