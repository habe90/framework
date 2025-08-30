<?php

namespace TrueFrame\Console\Commands;

use TrueFrame\Console\Command;
use RuntimeException;

class MakeControllerCommand extends Command
{
    protected string $signature = 'make:controller';
    protected string $description = 'Create a new controller class.';

    public function handle(): int
    {
        $name = $this->argument(0);

        if (empty($name)) {
            $this->error('Please provide a controller name.');
            return 1;
        }

        $resource = $this->option('resource');

        $stub = $resource ? 'controller.resource.stub' : 'controller.plain.stub';
        $content = file_get_contents(__DIR__ . "/stubs/{$stub}");

        $namespace = 'App\\Controllers';
        $className = str_replace('.php', '', ucfirst($name));

        $content = str_replace('{{ namespace }}', $namespace, $content);
        $content = str_replace('{{ class }}', $className, $content);

        $path = $this->app->basePath("app/Controllers/{$className}.php");

        if (file_exists($path)) {
            $this->error("Controller [{$className}] already exists!");
            return 1;
        }

        file_put_contents($path, $content);

        $this->info("Controller [{$className}] created successfully.");
        return 0;
    }
}