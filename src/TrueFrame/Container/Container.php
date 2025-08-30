<?php

namespace TrueFrame\Container;

use Closure;
use ReflectionClass;
use ReflectionParameter;

class Container
{
    /**
     * The container's bindings.
     *
     * @var array
     */
    protected array $bindings = [];

    /**
     * The container's shared instances.
     *
     * @var array
     */
    protected array $singletons = [];

    /**
     * An array of the types that have been resolved.
     *
     * @var array
     */
    protected array $resolved = [];

    /**
     * The registered aliases.
     *
     * @var array
     */
    protected array $aliases = [];

    /**
     * Bind a concrete implementation to an abstract type.
     *
     * @param string $abstract
     * @param Closure|string|null $concrete
     * @param bool $shared
     * @return void
     */
    public function bind(string $abstract, Closure|string $concrete = null, bool $shared = false): void
    {
        if (is_null($concrete)) {
            $concrete = $abstract;
        }

        if (!($concrete instanceof Closure)) {
            $concrete = fn($container) => $container->build($concrete);
        }

        $this->bindings[$abstract] = compact('concrete', 'shared');
    }

    /**
     * Bind a shared (singleton) concrete implementation to an abstract type.
     *
     * @param string $abstract
     * @param Closure|string|null $concrete
     * @return void
     */
    public function singleton(string $abstract, Closure|string $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * Register an existing instance as a shared singleton.
     *
     * @param string $abstract
     * @param mixed $instance
     * @return mixed
     */
    public function instance(string $abstract, mixed $instance): mixed
    {
        unset($this->aliases[$abstract]);
        unset($this->bindings[$abstract]);

        $this->singletons[$abstract] = $instance;

        $this->resolved[$abstract] = true;

        return $instance;
    }

    /**
     * Resolve the given type from the container.
     *
     * @param string $abstract
     * @param array $parameters
     * @return mixed
     * @throws ContainerException
     */
    public function make(string $abstract, array $parameters = []): mixed
    {
        $abstract = $this->getAlias($abstract);

        // If an instance of the type is already shared, we'll just return it.
        if (isset($this->singletons[$abstract])) {
            return $this->singletons[$abstract];
        }

        $concrete = $this->getConcrete($abstract);

        // If we don't find a concrete type, we'll just assume it's the same as the abstract.
        if ($concrete === $abstract) {
            $object = $this->build($concrete);
        } else {
            $object = $this->resolve($concrete, $parameters);
        }

        // If the binding is a singleton, we'll store it for future use.
        if (isset($this->bindings[$abstract]['shared']) && $this->bindings[$abstract]['shared']) {
            $this->singletons[$abstract] = $object;
        }

        $this->resolved[$abstract] = true;

        return $object;
    }

    /**
     * Get the concrete type for a given abstract.
     *
     * @param string $abstract
     * @return Closure|string
     */
    protected function getConcrete(string $abstract): Closure|string
    {
        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]['concrete'];
        }

        return $abstract;
    }

    /**
     * Resolve the given concrete type from the container.
     *
     * @param Closure|string $concrete
     * @param array $parameters
     * @return mixed
     * @throws ContainerException
     */
    protected function resolve(Closure|string $concrete, array $parameters): mixed
    {
        if ($concrete instanceof Closure) {
            return $concrete($this, $parameters);
        }

        return $this->build($concrete);
    }

    /**
     * Instantiate a concrete instance of the given type.
     *
     * @param string $concrete
     * @return mixed
     * @throws ContainerException
     */
    public function build(string $concrete): mixed
    {
        try {
            $reflector = new ReflectionClass($concrete);
        } catch (\ReflectionException $e) {
            throw new ContainerException("Target class [$concrete] does not exist.", 0, $e);
        }

        if (!$reflector->isInstantiable()) {
            throw new ContainerException("Target class [$concrete] is not instantiable.");
        }

        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            return new $concrete;
        }

        $dependencies = $constructor->getParameters();

        $instances = $this->resolveDependencies($dependencies);

        return $reflector->newInstanceArgs($instances);
    }

    /**
     * Resolve all of the dependencies from the given array of parameters.
     *
     * @param array<int, ReflectionParameter> $dependencies
     * @return array
     * @throws ContainerException
     */
    protected function resolveDependencies(array $dependencies): array
    {
        $results = [];

        foreach ($dependencies as $dependency) {
            if ($dependency->isDefaultValueAvailable()) {
                $results[] = $dependency->getDefaultValue();
            } elseif ($dependency->hasType() && !$dependency->getType()->isBuiltin()) {
                $results[] = $this->make($dependency->getType()->getName());
            } else {
                throw new ContainerException("Unresolvable dependency: Parameter '{$dependency->getName()}' of type '{$dependency->getType()}' in class '{$dependency->getDeclaringClass()->getName()}'");
            }
        }

        return $results;
    }

    /**
     * Register an alias for a binding.
     *
     * @param string $abstract
     * @param string $alias
     * @return void
     */
    public function alias(string $abstract, string $alias): void
    {
        $this->aliases[$alias] = $abstract;
    }

    /**
     * Get the alias for an abstract if it exists.
     *
     * @param string $abstract
     * @return string
     */
    public function getAlias(string $abstract): string
    {
        return $this->aliases[$abstract] ?? $abstract;
    }

    /**
     * Flush the container of all bindings and resolved instances.
     *
     * @return void
     */
    public function flush(): void
    {
        $this->bindings = [];
        $this->singletons = [];
        $this->resolved = [];
        $this->aliases = [];
    }

    /**
     * Call the given Closure / class method and inject its dependencies.
     *
     * @param callable|array $callback
     * @param array $parameters
     * @return mixed
     */
    public function call(callable|array $callback, array $parameters = []): mixed
    {
        if (is_string($callback) && str_contains($callback, '@')) {
            $callback = $this->createCallable($callback);
        }

        $reflection = is_array($callback)
            ? new \ReflectionMethod($callback[0], $callback[1])
            : new \ReflectionFunction($callback);

        $dependencies = $this->resolveMethodDependencies($parameters, $reflection);

        return $reflection->invokeArgs($this->make($callback[0]), $dependencies);
    }

    /**
     * Create a callable for a "Controller@method" string.
     *
     * @param string $callback
     * @return array
     */
    protected function createCallable(string $callback): array
    {
        [$controller, $method] = explode('@', $callback);

        return [$controller, $method];
    }

    /**
     * Resolve all of the dependencies for a given method.
     *
     * @param array $parameters
     * @param \ReflectionFunctionAbstract $reflector
     * @return array
     */
    protected function resolveMethodDependencies(array $parameters, \ReflectionFunctionAbstract $reflector): array
    {
        $newDependencies = [];

        foreach ($reflector->getParameters() as $parameter) {
            if (array_key_exists($parameter->name, $parameters)) {
                $newDependencies[] = $parameters[$parameter->name];
                unset($parameters[$parameter->name]); // Remove so it's not used again
            } elseif ($parameter->hasType() && !$parameter->getType()->isBuiltin()) {
                $newDependencies[] = $this->make($parameter->getType()->getName());
            } elseif ($parameter->isDefaultValueAvailable()) {
                $newDependencies[] = $parameter->getDefaultValue();
            } else {
                // If a parameter is not resolved by type-hinting or default value,
                // and not provided in $parameters, we throw an exception.
                throw new ContainerException("Unresolvable dependency: Parameter '{$parameter->name}' of type '{$parameter->getType()}' in method '{$reflector->getName()}'");
            }
        }

        return array_merge($newDependencies, $parameters); // Append any remaining parameters
    }
}