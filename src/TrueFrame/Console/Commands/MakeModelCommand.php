<?php

namespace TrueFrame\Console\Commands;

use TrueFrame\Console\Command;
use RuntimeException;

class MakeModelCommand extends Command
{
    protected string $signature = 'make:model';
    protected string $description = 'Create a new Eloquent model class.';

    public function handle(): int
    {
        $name = $this->argument(0);

        if (empty($name)) {
            $this->error('Please provide a model name.');
            return 1;
        }

        $className = str_replace('.php', '', ucfirst($name));
        $content = file_get_contents(__DIR__ . "/stubs/model.stub");

        // Simple default values for fillable and casts
        $fillable = "        // 'name',";
        $casts = "        []"; // Default empty array

        $content = str_replace('{{ namespace }}', 'App\\Models', $content);
        $content = str_replace('{{ class }}', $className, $content);
        $content = str_replace('{{ table }}', strtolower($className) . 's', $content); // Simple pluralization for table name
        $content = str_replace('{{ fillable }}', $fillable, $content);
        $content = str_replace('{{ casts }}', $casts, $content);


        $path = $this->app->basePath("app/Models/{$className}.php");

        if (file_exists($path)) {
            $this->error("Model [{$className}] already exists!");
            return 1;
        }

        file_put_contents($path, $content);

        $this->info("Model [{$className}] created successfully.");

        if ($this->option('m')) {
            $this->call('make:migration', ['create_' . strtolower($className) . 's_table']);
        }

        return 0;
    }

    /**
     * Call another console command.
     *
     * @param string $command
     * @param array $args
     * @return int
     */
    protected function call(string $command, array $args = []): int
    {
        $console = $this->app->make(\TrueFrame\Console\Application::class);
        return $console->run(array_merge([$command], $args));
    }
}