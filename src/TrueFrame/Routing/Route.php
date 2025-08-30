<?php

namespace TrueFrame\Routing;

use Closure;
use InvalidArgumentException;
use TrueFrame\Http\Request;
use TrueFrame\Http\Response;
use TrueFrame\Http\Middleware\MiddlewareInterface;

class Route
{
    protected string $method;
    protected string $uri;
    protected mixed $action;
    protected array $parameters = [];
    protected array $middlewares = [];
    protected array $wheres = [];

    public function __construct(string $method, string $uri, mixed $action)
    {
        $this->method = strtoupper($method);
        $this->uri = trim($uri, '/');
        $this->action = $action;
    }

    /**
     * Get the HTTP method of the route.
     *
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Get the URI of the route.
     *
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Get the action of the route.
     *
     * @return mixed
     */
    public function getAction(): mixed
    {
        return $this->action;
    }

    /**
     * Compile the route URI into a regex pattern.
     *
     * @return string
     */
    public function getRegex(): string
    {
        // Escape forward slashes, replace parameters with regex, and apply custom wheres
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^\/]+)', $this->uri);

        foreach ($this->wheres as $name => $regex) {
            $pattern = str_replace("(?P<{$name}>[^\/]+)", "(?P<{$name}>{$regex})", $pattern);
        }

        return '#^/' . $pattern . '$#';
    }

    /**
     * Set a regex constraint on a route parameter.
     *
     * @param string|array $name
     * @param string|null $regex
     * @return $this
     */
    public function where(string|array $name, string $regex = null): static
    {
        if (is_array($name)) {
            $this->wheres = array_merge($this->wheres, $name);
        } else {
            $this->wheres[$name] = $regex;
        }

        return $this;
    }

    /**
     * Add middleware to the route.
     *
     * @param string|array $middleware
     * @return $this
     */
    public function middleware(string|array $middleware): static
    {
        $middleware = (array) $middleware;
        $this->middlewares = array_merge($this->middlewares, $middleware);
        return $this;
    }

    /**
     * Get the middleware assigned to the route.
     *
     * @return array
     */
    public function getMiddleware(): array
    {
        return $this->middlewares;
    }

    /**
     * Set route parameters.
     *
     * @param array $parameters
     * @return $this
     */
    public function setParameters(array $parameters): static
    {
        $this->parameters = $parameters;
        return $this;
    }

    /**
     * Get route parameters.
     *
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Run the route action.
     *
     * @param Request $request
     * @return Response
     * @throws \Exception
     */
    public function run(Request $request): Response
    {
        $action = $this->action;

        // If the action is an array like [Controller::class, 'method'] or a Closure
        return app()->call($action, $this->parameters);
    }
}