<?php

namespace TrueFrame\Database;

use DateTime;
use TrueFrame\Application;
use TrueFrame\Container\ContainerException;
use PDOException;

abstract class Model
{
    /**
     * The table associated with the model.
     *
     * @var string|null
     */
    protected ?string $table = null;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected string $primaryKey = 'id';

    /**
     * The model's attributes.
     *
     * @var array
     */
    protected array $attributes = [];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected array $fillable = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected array $casts = [];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public bool $timestamps = true;

    /**
     * The name of the "created at" column.
     *
     * @var string|null
     */
    const CREATED_AT = 'created_at';

    /**
     * The name of the "updated at" column.
     *
     * @var string|null
     */
    const UPDATED_AT = 'updated_at';

    /**
     * Create a new Model instance.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    /**
     * Fill the model with an array of attributes.
     *
     * @param array $attributes
     * @return $this
     */
    public function fill(array $attributes): static
    {
        foreach ($this->fillableFromArray($attributes) as $key => $value) {
            $this->attributes[$key] = $value;
        }

        return $this;
    }

    /**
     * Get the fillable attributes of the given array.
     *
     * @param array $attributes
     * @return array
     */
    protected function fillableFromArray(array $attributes): array
    {
        if (count($this->fillable) > 0) {
            return array_intersect_key($attributes, array_flip($this->fillable));
        }
        return $attributes; // If $fillable is empty, allow all
    }

    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    public function getTable(): string
    {
        if (isset($this->table)) {
            return $this->table;
        }

        return strtolower(str_replace('\\', '', str_replace('App\\Models\\', '', class_basename($this))) . 's');
    }

    /**
     * Get the primary key for the model.
     *
     * @return string
     */
    public function getKeyName(): string
    {
        return $this->primaryKey;
    }

    /**
     * Get the value of the model's primary key.
     *
     * @return mixed
     */
    public function getKey(): mixed
    {
        return $this->attributes[$this->primaryKey] ?? null;
    }

    /**
     * Set the value of the model's primary key.
     *
     * @param mixed $value
     * @return $this
     */
    public function setKey(mixed $value): static
    {
        $this->attributes[$this->primaryKey] = $value;
        return $this;
    }

    /**
     * Get a new query builder for the model's table.
     *
     * @return QueryBuilder
     * @throws ContainerException
     */
    public static function query(): QueryBuilder
    {
        $instance = new static;
        $connection = app(Connection::class);
        return new QueryBuilder($connection, $instance->getTable());
    }

    /**
     * Find a model by its primary key.
     *
     * @param mixed $id
     * @return static|null
     * @throws ContainerException
     */
    public static function find(mixed $id): ?static
    {
        $data = static::query()->where((new static)->getKeyName(), '=', $id)->first();
        if ($data) {
            return (new static)->fill($data)->setKey($data[(new static)->getKeyName()]);
        }
        return null;
    }

    /**
     * Create a new model instance and save it to the database.
     *
     * @param array $attributes
     * @return static
     * @throws ContainerException|PDOException
     */
    public static function create(array $attributes = []): static
    {
        $model = new static($attributes);
        $model->save();
        return $model;
    }

    /**
     * Save the model to the database.
     *
     * @return bool
     * @throws ContainerException|PDOException
     */
    public function save(): bool
    {
        $query = static::query();

        $this->updateTimestamps();

        $attributesToSave = $this->attributes;

        if ($this->getKey()) {
            // Update existing record
            $affected = $query->where($this->getKeyName(), '=', $this->getKey())->update($attributesToSave);
            return $affected > 0;
        } else {
            // Insert new record
            $id = $query->insert($attributesToSave);
            if ($id) {
                $this->setKey($id);
                return true;
            }
            return false;
        }
    }

    /**
     * Update the model in the database.
     *
     * @param array $attributes
     * @return int
     * @throws ContainerException
     */
    public function update(array $attributes = []): int
    {
        $this->fill($attributes);
        $this->updateTimestamps();

        return static::query()
            ->where($this->getKeyName(), '=', $this->getKey())
            ->update($this->attributes);
    }

    /**
     * Delete the model from the database.
     *
     * @return int
     * @throws ContainerException
     */
    public function delete(): int
    {
        return static::query()->where($this->getKeyName(), '=', $this->getKey())->delete();
    }

    /**
     * Update the creation and update timestamps.
     *
     * @return void
     */
    protected function updateTimestamps(): void
    {
        if (!$this->timestamps) {
            return;
        }

        $time = (new DateTime())->format('Y-m-d H:i:s');

        if (!isset($this->attributes[static::CREATED_AT])) {
            $this->attributes[static::CREATED_AT] = $time;
        }

        $this->attributes[static::UPDATED_AT] = $time;
    }

    /**
     * Dynamically retrieve attributes on the model.
     *
     * @param string $key
     * @return mixed
     */
    public function __get(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    /**
     * Dynamically set attributes on the model.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function __set(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Determine if an attribute or relation exists on the model.
     *
     * @param string $key
     * @return bool
     */
    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    /**
     * Unset an attribute on the model.
     *
     * @param string $key
     * @return void
     */
    public function __unset(string $key): void
    {
        unset($this->attributes[$key]);
    }

    /**
     * Handle dynamic static method calls into the model.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     * @throws \BadMethodCallException
     */
    public static function __callStatic(string $method, array $parameters): mixed
    {
        return static::query()->$method(...$parameters);
    }

    /**
     * Handle dynamic method calls into the model.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     * @throws \BadMethodCallException
     */
    public function __call(string $method, array $parameters): mixed
    {
        // Delegate to QueryBuilder
        $query = $this->query();

        if (method_exists($query, $method)) {
            return $query->$method(...$parameters);
        }

        throw new \BadMethodCallException(sprintf(
            'Method %s::%s does not exist.', static::class, $method
        ));
    }
}