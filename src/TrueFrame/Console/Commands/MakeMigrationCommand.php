<?php

namespace TrueFrame\Console\Commands;

use TrueFrame\Console\Command;
use DateTime;
use RuntimeException;

class MakeMigrationCommand extends Command
{
    protected string $signature = 'make:migration';
    protected string $description = 'Create a new migration file.';

    public function handle(): int
    {
        $name = $this->argument(0);

        if (empty($name)) {
            $this->error('Please provide a migration name.');
            return 1;
        }

        $className = $this->createClassName($name);
        $fileName = $this->createFileName($name);

        $content = file_get_contents(__DIR__ . "/stubs/migration.stub");
        $content = str_replace('{{ class }}', $className, $content);
        $content = str_replace('{{ table }}', strtolower($className) . 's', $content); // Simple pluralization for table name

        $path = $this->app->basePath("database/migrations/{$fileName}.php");

        if (file_exists($path)) {
            $this->error("Migration [{$fileName}] already exists!");
            return 1;
        }

        file_put_contents($path, $content);

        $this->info("Migration [{$fileName}] created successfully.");
        return 0;
    }

    /**
     * Create the migration class name from the given name.
     *
     * @param string $name
     * @return string
     */
    protected function createClassName(string $name): string
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $name)));
    }

    /**
     * Create the migration file name with a timestamp.
     *
     * @param string $name
     * @return string
     */
    protected function createFileName(string $name): string
    {
        $date = new DateTime();
        return $date->format('Y_m_d_His') . '_' . $name;
    }
}