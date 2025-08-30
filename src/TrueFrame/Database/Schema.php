<?php

namespace TrueFrame\Database;

use TrueFrame\Application;
use TrueFrame\Database\Schema\Blueprint;
use Closure;
use PDOException;

class Schema
{
    /**
     * The database connection instance.
     *
     * @var Connection
     */
    protected Connection $connection;

    /**
     * Create a new schema manager instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->connection = app(Connection::class);
    }

    /**
     * Create a new table on the schema.
     *
     * @param string $table
     * @param Closure $callback
     * @return void
     */
    public function create(string $table, Closure $callback): void
    {
        $blueprint = $this->getBlueprint($table);
        $blueprint->create();
        $callback($blueprint);
        $this->runBlueprint($blueprint);
    }

    /**
     * Drop a table from the schema.
     *
     * @param string $table
     * @return void
     */
    public function drop(string $table): void
    {
        $blueprint = $this->getBlueprint($table);
        $blueprint->drop();
        $this->runBlueprint($blueprint);
    }

    /**
     * Drop a table from the schema if it exists.
     *
     * @param string $table
     * @return void
     */
    public function dropIfExists(string $table): void
    {
        if ($this->hasTable($table)) {
            $this->drop($table);
        }
    }

    /**
     * Determine if the given table exists.
     *
     * @param string $table
     * @return bool
     */
    public function hasTable(string $table): bool
    {
        $driver = $this->connection->getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            $sql = "SELECT name FROM sqlite_master WHERE type='table' AND name=?";
        } else { // Assume MySQL-like
            $sql = "SHOW TABLES LIKE ?";
        }

        $result = $this->connection->selectOne($sql, [$table]);

        return !empty($result);
    }

    /**
     * Get a new Blueprint for the given table.
     *
     * @param string $table
     * @return Blueprint
     */
    protected function getBlueprint(string $table): Blueprint
    {
        return new Blueprint($table);
    }

    /**
     * Execute the blueprint to build the schema.
     *
     * @param Blueprint $blueprint
     * @return void
     * @throws PDOException
     */
    protected function runBlueprint(Blueprint $blueprint): void
    {
        foreach ($blueprint->toSql($this->connection->getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME)) as $statement) {
            $this->connection->statement($statement);
        }
    }
}