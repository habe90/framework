<?php

namespace TrueFrame\Console\Commands;

use TrueFrame\Console\Command;
use Database\Seeders\DatabaseSeeder;
use Exception;

class DbSeedCommand extends Command
{
    protected string $signature = 'db:seed';
    protected string $description = 'Seed the database with records.';

    public function handle(): int
    {
        $this->info('Seeding database...');

        $seeder = $this->argument(0, DatabaseSeeder::class);

        if (!class_exists($seeder)) {
            $this->error("Seeder class [{$seeder}] not found.");
            return 1;
        }

        try {
            $this->app->make($seeder)->run();
            $this->info('Database seeded successfully.');
            return 0;
        } catch (Exception $e) {
            $this->error("Database seeding failed: " . $e->getMessage());
            return 1;
        }
    }
}