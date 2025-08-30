<?php

namespace TrueFrame\Console\Commands;

use TrueFrame\Console\Command;
use RuntimeException;

class MakeRequestCommand extends Command
{
    protected string $signature = 'make:request';
    protected string $description = 'Create a new form request class.';

    public function handle(): int
    {
        $name = $this->argument(0);

        if (empty($name)) {
            $this->error('Please provide a request name.');
            return 1;
        }

        $content = file_get_contents(__DIR__ . "/stubs/request.stub");

        $namespace = 'App\\Http\\Requests';
        $className = str_replace('.php', '', ucfirst($name));

        $content = str_replace('{{ namespace }}', $namespace, $content);
        $content = str_replace('{{ class }}', $className, $content);
        $content = str_replace('{{ rules }}', '[]', $content); // Default empty rules

        $path = $this->app->basePath("app/Http/Requests/{$className}.php");

        if (file_exists($path)) {
            $this->error("Request [{$className}] already exists!");
            return 1;
        }

        file_put_contents($path, $content);

        $this->info("Request [{$className}] created successfully.");
        return 0;
    }
}