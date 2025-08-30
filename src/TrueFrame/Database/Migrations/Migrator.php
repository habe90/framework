<?php

namespace TrueFrame\Database\Migrations;

use TrueFrame\Application;
use TrueFrame\Database\Connection;
use TrueFrame\Database\Schema;
use PDOException;
use SplFileInfo;

class Migrator
{
    /**
     * The application instance.
     *
     * @var Application
     */
    protected Application $app;

    /**
     * The database connection instance.
     *
     * @var Connection
     */
    protected Connection $connection;

    /**
     * The migration repository instance.
     *
     * @var MigrationRepository
     */
    protected MigrationRepository $repository;

    /**
     * The migration paths.
     *
     * @var array
     */
    protected array $paths = [];

    /**
     * Create a new migrator instance.
     *
     * @param Application $app
     * @param Connection $connection
     * @param MigrationRepository $repository
     * @return void
     */
    public function __construct(Application $app, Connection $connection, MigrationRepository $repository)
    {
        $this->app = $app;
        $this->connection = $connection;
        $this->repository = $repository;
        $this->paths = [database_path('migrations')];
    }

    /**
     * Run the pending migrations.
     *
     * @param array $paths
     * @return array
     */
    public function run(array $paths = []): array
    {
        $this->repository->createRepository();

        $files = $this->getMigrationFiles($paths);
        $ran = $this->repository->getRanMigrations();

        $migrations = array_diff(array_keys($files), $ran);

        $this->runPending($migrations, $files);

        return $migrations;
    }

    /**
     * Run the pending migrations.
     *
     * @param array $migrations
     * @param array $files
     * @return void
     */
    protected function runPending(array $migrations, array $files): void
    {
        if (count($migrations) === 0) {
            echo "Nothing to migrate.\n";
            return;
        }

        $batch = $this->repository->getNextBatchNumber();

        foreach ($migrations as $migrationName) {
            $this->runUp($files[$migrationName], $migrationName, $batch);
        }
    }

    /**
     * Run an "up" migration instance.
     *
     * @param string $file
     * @param string $migrationName
     * @param int $batch
     * @return void
     */
    protected function runUp(string $file, string $migrationName, int $batch): void
    {
        $migration = $this->resolve($file);

        echo "Migrating: {$migrationName}\n";

        $migration->up();

        $this->repository->log($migrationName, $batch);

        echo "Migrated: {$migrationName}\n";
    }

    /**
     * Rollback the last migration operation.
     *
     * @param array $paths
     * @return array
     */
    public function rollback(array $paths = []): array
    {
        $this->repository->createRepository();

        $migrations = $this->repository->getLastBatch();

        if (count($migrations) === 0) {
            echo "Nothing to rollback.\n";
            return [];
        }

        $files = $this->getMigrationFiles($paths);

        foreach ($migrations as $migration) {
            $this->runDown($files[$migration->migration], $migration->migration);
            $this->repository->delete($migration->migration);
        }

        return array_column($migrations, 'migration');
    }

    /**
     * Run a "down" migration instance.
     *
     * @param string $file
     * @param string $migrationName
     * @return void
     */
    protected function runDown(string $file, string $migrationName): void
    {
        $migration = $this->resolve($file);

        echo "Rolling back: {$migrationName}\n";

        $migration->down();

        echo "Rolled back: {$migrationName}\n";
    }

    /**
     * Resolve a migration instance from a file.
     *
     * @param string $file
     * @return Migration
     */
    public function resolve(string $file): Migration
    {
        $path = $this->getMigrationPath($file);
        $class = $this->getMigrationClassName($file);

        require_once $path;

        return $this->app->make($class);
    }

    /**
     * Get all of the migration files in a given path.
     *
     * @param array $paths
     * @return array<string, string> (name => full path)
     */
    protected function getMigrationFiles(array $paths): array
    {
        $files = [];
        $paths = array_unique(array_merge($this->paths, $paths));

        foreach ($paths as $path) {
            if (!is_dir($path)) {
                continue;
            }
            foreach (glob($path . '/*_*.php') as $file) {
                $name = $this->getMigrationName(new SplFileInfo($file));
                $files[$name] = $file;
            }
        }

        ksort($files); // Sort by name (which includes timestamp)
        return $files;
    }

    /**
     * Get the name of the migration.
     *
     * @param SplFileInfo $file
     * @return string
     */
    public function getMigrationName(SplFileInfo $file): string
    {
        return str_replace('.php', '', $file->getFilename());
    }

    /**
     * Get the class name of a migration from its file.
     *
     * @param string $file The full path to the migration file.
     * @return string
     */
    protected function getMigrationClassName(string $file): string
    {
        // Extract the class name from the file path, e.g., 2023_01_01_000000_create_users_table.php -> CreateUsersTable
        $filename = pathinfo($file, PATHINFO_FILENAME);
        $segments = explode('_', $filename);
        // Remove timestamp segments
        $segments = array_slice($segments, 4);
        return str_replace(' ', '', ucwords(implode(' ', $segments)));
    }

    /**
     * Get the full path to a migration file.
     *
     * @param string $file The migration filename (e.g., '2023_01_01_000000_create_users_table').
     * @return string
     */
    protected function getMigrationPath(string $file): string
    {
        foreach ($this->paths as $path) {
            if (file_exists($path . DIRECTORY_SEPARATOR . $file . '.php')) {
                return $path . DIRECTORY_SEPARATOR . $file . '.php';
            }
        }
        return $file; // Fallback, might be a full path already
    }
}