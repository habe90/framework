<?php

namespace TrueFrame\Database\Migrations;

use TrueFrame\Database\Connection;
use TrueFrame\Database\Schema;
use PDOException;

class MigrationRepository
{
    /**
     * The database connection instance.
     *
     * @var Connection
     */
    protected Connection $connection;

    /**
     * The name of the migration table.
     *
     * @var string
     */
    protected string $table = 'migrations';

    /**
     * Create a new migration repository instance.
     *
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Create the migration repository data store.
     *
     * @return void
     */
    public function createRepository(): void
    {
        $schema = new Schema();
        if (!$schema->hasTable($this->table)) {
            $schema->create($this->table, function ($table) {
                $table->id();
                $table->string('migration');
                $table->integer('batch');
            });
            echo "Migration table created successfully.\n";
        }
    }

    /**
     * Get the ran migrations.
     *
     * @return array<string>
     */
    public function getRanMigrations(): array
    {
        return array_column($this->connection->select("SELECT migration FROM {$this->table} ORDER BY batch ASC, migration ASC"), 'migration');
    }

    /**
     * Get the last migration batch.
     *
     * @return array<object{migration: string, batch: int}>
     */
    public function getLastBatch(): array
    {
        $lastBatch = $this->connection->selectOne("SELECT MAX(batch) as max_batch FROM {$this->table}")['max_batch'] ?? 0;

        if ($lastBatch === 0) {
            return [];
        }

        // Fetch as objects for consistency with Laravel's approach
        return array_map(function($item) {
            return (object) $item;
        }, $this->connection->select("SELECT migration, batch FROM {$this->table} WHERE batch = ? ORDER BY migration ASC", [$lastBatch]));
    }

    /**
     * Log that a migration was run.
     *
     * @param string $file
     * @param int $batch
     * @return void
     */
    public function log(string $file, int $batch): void
    {
        $this->connection->statement("INSERT INTO {$this->table} (migration, batch) VALUES (?, ?)", [$file, $batch]);
    }

    /**
     * Delete a migration from the repository.
     *
     * @param string $file
     * @return void
     */
    public function delete(string $file): void
    {
        $this->connection->statement("DELETE FROM {$this->table} WHERE migration = ?", [$file]);
    }

    /**
     * Get the next migration batch number.
     *
     * @return int
     */
    public function getNextBatchNumber(): int
    {
        return $this->connection->selectOne("SELECT MAX(batch) as max_batch FROM {$this->table}")['max_batch'] + 1;
    }
}