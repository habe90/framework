<?php

namespace TrueFrame\Database\Schema;

use Closure;
use InvalidArgumentException;

class Blueprint
{
    /**
     * The table the blueprint describes.
     *
     * @var string
     */
    protected string $table;

    /**
     * The columns that should be added to the table.
     *
     * @var array<ColumnDefinition>
     */
    protected array $columns = [];

    /**
     * The commands that should be run for the table.
     *
     * @var array
     */
    protected array $commands = [];

    /**
     * Create a new schema blueprint instance.
     *
     * @param string $table
     * @return void
     */
    public function __construct(string $table)
    {
        $this->table = $table;
    }

    /**
     * Indicate that the table should be created.
     *
     * @return $this
     */
    public function create(): static
    {
        return $this->addCommand('create');
    }

    /**
     * Indicate that the table should be dropped.
     *
     * @return $this
     */
    public function drop(): static
    {
        return $this->addCommand('drop');
    }

    /**
     * Add a new column to the blueprint.
     *
     * @param string $type
     * @param string $name
     * @param array $parameters
     * @return ColumnDefinition
     */
    protected function addColumn(string $type, string $name, array $parameters = []): ColumnDefinition
    {
        $column = new ColumnDefinition(array_merge(compact('type', 'name'), $parameters));
        $this->columns[] = $column;
        return $column;
    }

    /**
     * Add an autoincrementing ID (primary key) column to the table.
     *
     * @param string $column
     * @return ColumnDefinition
     */
    public function id(string $column = 'id'): ColumnDefinition
    {
        return $this->unsignedBigInteger($column)->autoIncrement()->primary();
    }

    /**
     * Create a new string column on the blueprint.
     *
     * @param string $column
     * @param int $length
     * @return ColumnDefinition
     */
    public function string(string $column, int $length = 255): ColumnDefinition
    {
        return $this->addColumn('string', $column, compact('length'));
    }

    /**
     * Create a new text column on the blueprint.
     *
     * @param string $column
     * @return ColumnDefinition
     */
    public function text(string $column): ColumnDefinition
    {
        return $this->addColumn('text', $column);
    }

    /**
     * Create a new integer column on the blueprint.
     *
     * @param string $column
     * @return ColumnDefinition
     */
    public function integer(string $column): ColumnDefinition
    {
        return $this->addColumn('integer', $column);
    }

    /**
     * Create a new unsigned big integer column on the blueprint.
     *
     * @param string $column
     * @return ColumnDefinition
     */
    public function unsignedBigInteger(string $column): ColumnDefinition
    {
        return $this->addColumn('unsignedBigInteger', $column);
    }

    /**
     * Create a new UUID column on the blueprint.
     *
     * @param string $column
     * @return ColumnDefinition
     */
    public function uuid(string $column = 'uuid'): ColumnDefinition
    {
        return $this->addColumn('uuid', $column);
    }

    /**
     * Add nullable creation and update timestamps to the table.
     *
     * @return void
     */
    public function timestamps(): void
    {
        $this->addColumn('timestamp', 'created_at')->nullable();
        $this->addColumn('timestamp', 'updated_at')->nullable();
    }

    /**
     * Add an index to a column.
     *
     * @param string|array $columns
     * @param string|null $name
     * @return $this
     */
    public function index(string|array $columns, ?string $name = null): static
    {
        return $this->addCommand('index', compact('columns', 'name'));
    }

    /**
     * Add a unique index to a column.
     *
     * @param string|array $columns
     * @param string|null $name
     * @return $this
     */
    public function unique(string|array $columns, ?string $name = null): static
    {
        // For single column unique constraints, we can set it directly on the column
        if (is_string($columns) && count($this->columns) > 0 && $this->columns[count($this->columns) - 1]->name === $columns) {
            $this->columns[count($this->columns) - 1]->unique = true;
            return $this;
        }
        return $this->addCommand('unique', compact('columns', 'name'));
    }

    /**
     * Add a foreign key constraint.
     *
     * @param string $column
     * @return ColumnDefinition
     */
    public function foreignId(string $column): ColumnDefinition
    {
        return $this->unsignedBigInteger($column); // The `constrained` method will add the actual foreign key command
    }

    /**
     * Add a command to the blueprint.
     *
     * @param string $name
     * @param array $parameters
     * @return $this
     */
    protected function addCommand(string $name, array $parameters = []): static
    {
        $this->commands[] = compact('name', 'parameters');
        return $this;
    }

    /**
     * Get the raw SQL statements for the blueprint.
     *
     * @param string $driver
     * @return array<string>
     * @throws InvalidArgumentException
     */
    public function toSql(string $driver): array
    {
        $sqls = [];

        foreach ($this->commands as $command) {
            $method = 'compile' . ucfirst($command['name']);
            if (method_exists($this, $method)) {
                $sqls[] = $this->$method($command['parameters'], $driver);
            } else {
                throw new InvalidArgumentException("Unknown command: {$command['name']}");
            }
        }

        // Add foreign key constraints after table creation
        if ($this->hasCommand('create')) {
            foreach ($this->columns as $column) {
                if ($column->constrained) {
                    $sqls[] = $this->compileForeign($column, $driver);
                }
            }
        }

        return $sqls;
    }

    /**
     * Check if a command exists in the blueprint.
     *
     * @param string $name
     * @return bool
     */
    protected function hasCommand(string $name): bool
    {
        foreach ($this->commands as $command) {
            if ($command['name'] === $name) {
                return true;
            }
        }
        return false;
    }

    /**
     * Compile a create table command.
     *
     * @param array $parameters
     * @param string $driver
     * @return string
     */
    protected function compileCreate(array $parameters, string $driver): string
    {
        $columnsSql = $this->getColumnsSql($driver);
        $primaryKeys = $this->getPrimaryKeysSql();
        $uniqueKeys = $this->getUniqueKeysSql();

        $tableElements = array_filter([$columnsSql, $primaryKeys, $uniqueKeys]);

        return "CREATE TABLE {$this->table} (" . implode(', ', $tableElements) . ")";
    }

    /**
     * Compile a drop table command.
     *
     * @param array $parameters
     * @param string $driver
     * @return string
     */
    protected function compileDrop(array $parameters, string $driver): string
    {
        return "DROP TABLE {$this->table}";
    }

    /**
     * Compile an index command.
     *
     * @param array $parameters
     * @param string $driver
     * @return string
     */
    protected function compileIndex(array $parameters, string $driver): string
    {
        $columns = (array) $parameters['columns'];
        $name = $parameters['name'] ?? $this->createIndexName('index', $columns);
        $columnsSql = implode(', ', $columns);
        return "CREATE INDEX {$name} ON {$this->table} ({$columnsSql})";
    }

    /**
     * Compile a unique constraint command.
     *
     * @param array $parameters
     * @param string $driver
     * @return string
     */
    protected function compileUnique(array $parameters, string $driver): string
    {
        $columns = (array) $parameters['columns'];
        $name = $parameters['name'] ?? $this->createIndexName('unique', $columns);
        $columnsSql = implode(', ', $columns);
        return "ALTER TABLE {$this->table} ADD CONSTRAINT {$name} UNIQUE ({$columnsSql})";
    }

    /**
     * Compile a foreign key constraint.
     *
     * @param ColumnDefinition $column
     * @param string $driver
     * @return string
     */
    protected function compileForeign(ColumnDefinition $column, string $driver): string
    {
        $foreignTable = $column->foreignTable ?? strtolower(str_replace('_id', '', $column->name)) . 's';
        $foreignColumn = $column->foreignColumn ?? 'id';
        $constraintName = $this->createIndexName('foreign', [$column->name, $foreignTable, $foreignColumn]);

        return "ALTER TABLE {$this->table} ADD CONSTRAINT {$constraintName} FOREIGN KEY ({$column->name}) REFERENCES {$foreignTable} ({$foreignColumn})";
    }

    /**
     * Create a default index name for the table.
     *
     * @param string $type
     * @param array $columns
     * @return string
     */
    protected function createIndexName(string $type, array $columns): string
    {
        $name = strtolower($this->table . '_' . implode('_', $columns) . '_' . $type);
        return str_replace(['-', '.'], '_', $name);
    }

    /**
     * Get the SQL for the column definitions.
     *
     * @param string $driver
     * @return string
     */
    protected function getColumnsSql(string $driver): string
    {
        $statements = [];
        foreach ($this->columns as $column) {
            $statements[] = $this->getColumnSql($column, $driver);
        }
        return implode(', ', $statements);
    }

    /**
     * Get the SQL for a single column.
     *
     * @param ColumnDefinition $column
     * @param string $driver
     * @return string
     */
    protected function getColumnSql(ColumnDefinition $column, string $driver): string
    {
        $sql = "{$column->name} " . $this->mapTypeToSql($column, $driver);

        if ($column->autoIncrement) {
            if ($driver === 'sqlite') {
                $sql .= ' PRIMARY KEY AUTOINCREMENT';
            } else { // MySQL
                $sql .= ' AUTO_INCREMENT';
            }
        }

        if ($column->nullable) {
            $sql .= ' NULL';
        } else {
            $sql .= ' NOT NULL';
        }

        if ($column->default !== null) {
            $defaultValue = is_string($column->default) ? "'{$column->default}'" : $column->default;
            $sql .= " DEFAULT {$defaultValue}";
        }

        // Unique constraint can be added directly for single columns in CREATE TABLE
        if ($column->unique && !$column->primary && $driver !== 'sqlite') { // SQLite handles unique with PRIMARY KEY or separate UNIQUE constraint
            $sql .= ' UNIQUE';
        }

        return $sql;
    }

    /**
     * Get the SQL for primary key constraints.
     *
     * @return string|null
     */
    protected function getPrimaryKeysSql(): ?string
    {
        $primaryColumns = [];
        foreach ($this->columns as $column) {
            if ($column->primary && !$column->autoIncrement) { // AUTO_INCREMENT implies PRIMARY KEY for most DBs
                $primaryColumns[] = $column->name;
            }
        }
        return !empty($primaryColumns) ? "PRIMARY KEY (" . implode(', ', $primaryColumns) . ")" : null;
    }

    /**
     * Get the SQL for unique key constraints (for columns not marked as primary).
     *
     * @return string|null
     */
    protected function getUniqueKeysSql(): ?string
    {
        $uniqueColumns = [];
        foreach ($this->columns as $column) {
            if ($column->unique && !$column->primary) {
                $uniqueColumns[] = $column->name;
            }
        }
        return !empty($uniqueColumns) ? "UNIQUE (" . implode(', ', $uniqueColumns) . ")" : null;
    }

    /**
     * Map a column type to its SQL equivalent.
     *
     * @param ColumnDefinition $column
     * @param string $driver
     * @return string
     */
    protected function mapTypeToSql(ColumnDefinition $column, string $driver): string
    {
        return match ($column->type) {
            'string' => "VARCHAR({$column->length})",
            'text' => 'TEXT',
            'integer' => 'INT',
            'unsignedBigInteger' => ($driver === 'sqlite' ? 'INTEGER' : 'BIGINT UNSIGNED'),
            'timestamp', 'datetime' => 'DATETIME',
            'date' => 'DATE',
            'boolean' => ($driver === 'sqlite' ? 'INTEGER' : 'TINYINT(1)'),
            'float' => 'FLOAT',
            'double' => 'DOUBLE',
            'uuid' => "CHAR(36)", // UUIDs are typically CHAR(36) or VARCHAR(36)
            default => throw new InvalidArgumentException("Unsupported column type: {$column->type}")
        };
    }
}