<?php

namespace TrueFrame\Console\Commands;

use TrueFrame\Console\Command;
use Exception;

class AIScaffoldCommand extends Command
{
    protected string $signature = 'ai:scaffold';
    protected string $description = 'Scaffold a resource (model, controller, migration, views, routes) using AI hook.';

    public function handle(): int
    {
        $resourceName = $this->argument(0);
        if (empty($resourceName)) {
            $this->error("Please provide a resource name (e.g., 'Post').");
            return 1;
        }

        $fields = [];
        foreach (array_slice($this->argument(), 1) as $arg) {
            if (str_contains($arg, ':')) {
                [$fieldName, $fieldType] = explode(':', $arg);
                $fields[$fieldName] = $fieldType;
            } else {
                $this->warn("Ignoring invalid field format: {$arg}. Expected 'name:type'.");
            }
        }

        $flags = $this->option(); // Get all options as flags

        try {
            $this->scaffolder()->scaffold($resourceName, $fields, $flags);
            $this->info("Resource '{$resourceName}' scaffolded successfully (using NullScaffolder).");
            return 0;
        } catch (Exception $e) {
            $this->error("Scaffolding failed: " . $e->getMessage());
            return 1;
        }
    }
}