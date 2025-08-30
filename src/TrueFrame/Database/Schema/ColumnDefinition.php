<?php

namespace TrueFrame\Database\Schema;

class ColumnDefinition
{
    /**
     * The column's name.
     *
     * @var string
     */
    public string $name;

    /**
     * The column's type.
     *
     * @var string
     */
    public string $type;

    /**
     * The column's length.
     *
     * @var int|null
     */
    public ?int $length = null;

    /**
     * Indicates if the column should be nullable.
     *
     * @var bool
     */
    public bool $nullable = false;

    /**
     * The column's default value.
     *
     * @var mixed
     */
    public mixed $default = null;

    /**
     * Indicates if the column is a primary key.
     *
     * @var bool
     */
    public bool $primary = false;

    /**
     * Indicates if the column should be auto-incrementing.
     *
     * @var bool
     */
    public bool $autoIncrement = false;

    /**
     * Indicates if the column should be unique.
     *
     * @var bool
     */
    public bool $unique = false;

    /**
     * The foreign key table.
     *
     * @var string|null
     */
    public ?string $foreignTable = null;

    /**
     * The foreign key column.
     *
     * @var string|null
     */
    public ?string $foreignColumn = null;

    /**
     * Indicates if the foreign key should be constrained.
     *
     * @var bool
     */
    public bool $constrained = false;

    /**
     * Create a new column definition.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        foreach ($attributes as $key => $value) {
            $this->{$key} = $value;
        }
    }

    /**
     * Set the column to be nullable.
     *
     * @return $this
     */
    public function nullable(): static
    {
        $this->nullable = true;
        return $this;
    }

    /**
     * Set the column's default value.
     *
     * @param mixed $value
     * @return $this
     */
    public function default(mixed $value): static
    {
        $this->default = $value;
        return $this;
    }

    /**
     * Set the column as a primary key.
     *
     * @return $this
     */
    public function primary(): static
    {
        $this->primary = true;
        return $this;
    }

    /**
     * Set the column to be auto-incrementing.
     *
     * @return $this
     */
    public function autoIncrement(): static
    {
        $this->autoIncrement = true;
        return $this;
    }

    /**
     * Set the column to be unique.
     *
     * @return $this
     */
    public function unique(): static
    {
        $this->unique = true;
        return $this;
    }

    /**
     * Indicate that the column is a foreign key.
     *
     * @param string|null $table The table this column references.
     * @param string $column The column on the foreign table.
     * @return $this
     */
    public function foreign(?string $table = null, string $column = 'id'): static
    {
        $this->foreignTable = $table;
        $this->foreignColumn = $column;
        return $this;
    }

    /**
     * Indicate that the foreign key should be constrained.
     *
     * @param string|null $table
     * @param string|null $column
     * @return $this
     */
    public function constrained(?string $table = null, ?string $column = null): static
    {
        $this->constrained = true;
        $this->foreignTable = $table ?? $this->foreignTable;
        $this->foreignColumn = $column ?? $this->foreignColumn;
        return $this;
    }
}