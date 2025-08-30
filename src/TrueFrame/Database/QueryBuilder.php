<?php

namespace TrueFrame\Database;

use PDO;
use PDOException;

class QueryBuilder
{
    /**
     * The database connection instance.
     *
     * @var Connection
     */
    protected Connection $connection;

    /**
     * The table name.
     *
     * @var string
     */
    protected string $table;

    /**
     * The columns to select.
     *
     * @var array
     */
    protected array $selects = ['*'];

    /**
     * The "where" clauses for the query.
     *
     * @var array
     */
    protected array $wheres = [];

    /**
     * The "order by" clauses for the query.
     *
     * @var array
     */
    protected array $orders = [];

    /**
     * The maximum number of records to return.
     *
     * @var int|null
     */
    protected ?int $limit = null;

    /**
     * Create a new query builder instance.
     *
     * @param Connection $connection
     * @param string $table
     */
    public function __construct(Connection $connection, string $table)
    {
        $this->connection = $connection;
        $this->table = $table;
    }

    /**
     * Set the columns to be selected.
     *
     * @param array|string $columns
     * @return $this
     */
    public function select(array|string $columns = ['*']): static
    {
        $this->selects = is_array($columns) ? $columns : func_get_args();
        return $this;
    }

    /**
     * Add a basic where clause to the query.
     *
     * @param string $column
     * @param string $operator
     * @param mixed $value
     * @return $this
     */
    public function where(string $column, string $operator, mixed $value): static
    {
        $this->wheres[] = compact('column', 'operator', 'value');
        return $this;
    }

    /**
     * Add an "order by" clause to the query.
     *
     * @param string $column
     * @param string $direction
     * @return $this
     */
    public function orderBy(string $column, string $direction = 'asc'): static
    {
        $this->orders[] = compact('column', 'direction');
        return $this;
    }

    /**
     * Set the maximum number of records to return.
     *
     * @param int $value
     * @return $this
     */
    public function limit(int $value): static
    {
        $this->limit = $value;
        return $this;
    }

    /**
     * Execute the query and get the first result.
     *
     * @return array|null
     */
    public function first(): ?array
    {
        $this->limit(1);
        $results = $this->get();
        return $results[0] ?? null;
    }

    /**
     * Execute the query and get all results.
     *
     * @return array
     */
    public function get(): array
    {
        [$sql, $bindings] = $this->toSqlAndBindings();
        return $this->connection->select($sql, $bindings);
    }

    /**
     * Insert a new record into the database.
     *
     * @param array $values
     * @return int|false The ID of the last inserted row or false on failure.
     * @throws PDOException
     */
    public function insert(array $values): int|false
    {
        $keys = array_keys($values);
        $columns = implode(', ', $keys);
        $placeholders = implode(', ', array_fill(0, count($keys), '?'));

        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";

        $this->connection->statement($sql, array_values($values));

        return $this->connection->lastInsertId();
    }

    /**
     * Update records in the database.
     *
     * @param array $values
     * @return int The number of affected rows.
     */
    public function update(array $values): int
    {
        $setClauses = [];
        $bindings = [];

        foreach ($values as $key => $value) {
            $setClauses[] = "{$key} = ?";
            $bindings[] = $value;
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $setClauses);

        [$whereSql, $whereBindings] = $this->compileWheres();
        if ($whereSql) {
            $sql .= " WHERE {$whereSql}";
            $bindings = array_merge($bindings, $whereBindings);
        }

        return $this->connection->statement($sql, $bindings);
    }

    /**
     * Delete records from the database.
     *
     * @return int The number of affected rows.
     */
    public function delete(): int
    {
        $sql = "DELETE FROM {$this->table}";

        [$whereSql, $whereBindings] = $this->compileWheres();
        if ($whereSql) {
            $sql .= " WHERE {$whereSql}";
        }

        return $this->connection->statement($sql, $whereBindings);
    }

    /**
     * Compile the query into SQL and bindings.
     *
     * @return array{string, array}
     */
    protected function toSqlAndBindings(): array
    {
        $sql = "SELECT " . implode(', ', $this->selects) . " FROM {$this->table}";
        $bindings = [];

        [$whereSql, $whereBindings] = $this->compileWheres();
        if ($whereSql) {
            $sql .= " WHERE {$whereSql}";
            $bindings = array_merge($bindings, $whereBindings);
        }

        if (!empty($this->orders)) {
            $orderClauses = [];
            foreach ($this->orders as $order) {
                $orderClauses[] = "{$order['column']} {$order['direction']}";
            }
            $sql .= " ORDER BY " . implode(', ', $orderClauses);
        }

        if ($this->limit !== null) {
            $sql .= " LIMIT ?";
            $bindings[] = $this->limit;
        }

        return [$sql, $bindings];
    }

    /**
     * Compile the "where" clauses.
     *
     * @return array{string, array}
     */
    protected function compileWheres(): array
    {
        if (empty($this->wheres)) {
            return ['', []];
        }

        $sql = [];
        $bindings = [];

        foreach ($this->wheres as $where) {
            $sql[] = "{$where['column']} {$where['operator']} ?";
            $bindings[] = $where['value'];
        }

        return [implode(' AND ', $sql), $bindings];
    }
}