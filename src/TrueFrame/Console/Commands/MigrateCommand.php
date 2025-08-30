<?php

namespace TrueFrame\Console\Commands;

use TrueFrame\Console\Command;
use Exception;

class MigrateCommand extends Command
{
    protected string $signature = 'migrate';
    protected string $description = 'Run the database migrations.';

    public function handle(): int
    {
        try {
            $this->migrator()->run();
            return 0;
        } catch (Exception $e) {
            $this->error("Migration failed: " . $e->getMessage());
            return 1;
        }
    }
}