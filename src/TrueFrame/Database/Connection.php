<?php

namespace TrueFrame\Database;

use PDO;
use PDOException;
use TrueFrame\Config\Repository;
use InvalidArgumentException;

class Connection
{
    /**
     * The PDO connection instance.
     *
     * @var PDO
     */
    protected PDO $pdo;

    /**
     * The database configuration.
     *
     * @var array
     */
    protected array $config;

    /**
     * Create a new database connection instance.
     *
     * @param Repository $config
     * @return void
     */
    public function __construct(Repository $config)
    {
        $this->config = $config->get('database.connections.' . $config->get('database.default'));
        $this->connect();
    }

    /**
     * Establish a PDO connection.
     *
     * @return void
     * @throws PDOException
     * @throws InvalidArgumentException
     */
    protected function connect(): void
    {
        $driver = $this->config['driver'] ?? null;

        if (!$driver) {
            throw new InvalidArgumentException("A database driver must be specified.");
        }

        switch ($driver) {
            case 'mysql':
                $dsn = $this->getMysqlDsn();
                break;
            case 'sqlite':
                $dsn = $this->getSqliteDsn();
                break;
            default:
                throw new InvalidArgumentException("Unsupported database driver [{$driver}].");
        }

        try {
            $this->pdo = new PDO(
                $dsn,
                $this->config['username'] ?? null,
                $this->config['password'] ?? null,
                $this->getOptions()
            );
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new PDOException("Could not connect to the database: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get the MySQL DSN string.
     *
     * @return string
     */
    protected function getMysqlDsn(): string
    {
        $host = $this->config['host'] ?? '127.0.0.1';
        $port = $this->config['port'] ?? '3306';
        $database = $this->config['database'] ?? '';
        $charset = $this->config['charset'] ?? 'utf8mb4';

        return "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";
    }

    /**
     * Get the SQLite DSN string.
     *
     * @return string
     */
    protected function getSqliteDsn(): string
    {
        $database = $this->config['database'] ?? database_path('database.sqlite');
        return "sqlite:{$database}";
    }

    /**
     * Get the PDO connection options.
     *
     * @return array
     */
    protected function getOptions(): array
    {
        return $this->config['options'] ?? [
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => false,
        ];
    }

    /**
     * Get the PDO connection instance.
     *
     * @return PDO
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Execute a SQL statement and return the number of affected rows.
     *
     * @param string $sql
     * @param array $bindings
     * @return int
     */
    public function statement(string $sql, array $bindings = []): int
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($bindings);
        return $statement->rowCount();
    }

    /**
     * Execute a SQL statement and return all results.
     *
     * @param string $sql
     * @param array $bindings
     * @return array
     */
    public function select(string $sql, array $bindings = []): array
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($bindings);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Execute a SQL statement and return the first result.
     *
     * @param string $sql
     * @param array $bindings
     * @return array|null
     */
    public function selectOne(string $sql, array $bindings = []): ?array
    {
        $result = $this->select($sql, $bindings);
        return $result[0] ?? null;
    }

    /**
     * Get the ID of the last inserted row.
     *
     * @param string|null $name
     * @return string|false
     */
    public function lastInsertId(?string $name = null): string|false
    {
        return $this->pdo->lastInsertId($name);
    }
}