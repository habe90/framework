<?php

namespace TrueFrame\Database;

use PDO;
use PDOException;

/**
 * Very basic ORM placeholder.
 * In a real framework, this would be a full-fledged ORM like Eloquent or Doctrine.
 */
class ORM
{
    protected PDO $pdo;

    public function __construct(array $config)
    {
        try {
            $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
            $this->pdo = new PDO($dsn, $config['username'], $config['password'], $config['options'] ?? []);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new \RuntimeException("Database connection failed: " . $e->getMessage());
        }
    }

    /**
     * Execute a raw SQL query.
     *
     * @param string $sql
     * @param array $params
     * @return array
     */
    public function query(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Get a single record.
     *
     * @param string $table
     * @param int $id
     * @return array|null
     */
    public function find(string $table, int $id): ?array
    {
        $result = $this->query("SELECT * FROM {$table} WHERE id = ?", [$id]);
        return $result[0] ?? null;
    }

    /**
     * Insert a record.
     *
     * @param string $table
     * @param array $data
     * @return int The last inserted ID.
     */
    public function insert(string $table, array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_values($data));

        return (int) $this->pdo->lastInsertId();
    }

    // ... other methods like update, delete, all, where, etc.
}