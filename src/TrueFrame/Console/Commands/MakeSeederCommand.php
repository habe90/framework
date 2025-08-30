<?php

namespace TrueFrame\Console\Commands;

use TrueFrame\Console\Command;
use RuntimeException;

class MakeSeederCommand extends Command
{
    protected string $signature = 'make:seeder';
    protected string $description = 'Create a new database seeder class.';

    public function handle(): int
    {
        $name = $this->argument(0);

        if (empty($name)) {
            $this->error('Please provide a seeder name.');
            return 1;
        }

        $className = str_replace('.php', '', ucfirst($name));
        $content = file_get_contents(__DIR__ . "/stubs/seeder.stub");

        $content = str_replace('{{ namespace }}', 'Database\\Seeders', $content);
        $content = str_replace('{{ class }}', $className, $content);

        $path = $this->app->basePath("database/seeders/{$className}.php");

        if (file_exists($path)) {
            $this->error("Seeder [{$className}] already exists!");
            return 1;
        }

        file_put_contents($path, $content);

        $this->info("Seeder [{$className}] created successfully.");
        return 0;
    }
}