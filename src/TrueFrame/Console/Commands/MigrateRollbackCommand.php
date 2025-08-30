<?php

namespace TrueFrame\Console\Commands;

use TrueFrame\Console\Command;
use Exception;

class MigrateRollbackCommand extends Command
{
    protected string $signature = 'migrate:rollback';
    protected string $description = 'Rollback the last database migration.';

    public function handle(): int
    {
        try {
            $this->migrator()->rollback();
            return 0;
        } catch (Exception $e) {
            $this->error("Migration rollback failed: " . $e->getMessage());
            return 1;
        }
    }
}