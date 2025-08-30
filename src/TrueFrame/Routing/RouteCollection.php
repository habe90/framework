<?php

namespace TrueFrame\Routing;

class RouteCollection
{
    /**
     * All of the routes that have been registered.
     *
     * @var array<string, array<Route>>
     */
    protected array $routes = [];

    /**
     * Add a route to the collection.
     *
     * @param Route $route
     * @return Route
     */
    public function add(Route $route): Route
    {
        $this->routes[$route->getMethod()][] = $route;
        return $route;
    }

    /**
     * Find the first route matching a given request.
     *
     * @param string $method
     * @param string $uri
     * @return Route|null
     */
    public function match(string $method, string $uri): ?Route
    {
        $uri = trim($uri, '/');

        foreach ($this->routes[$method] ?? [] as $route) {
            if (preg_match($route->getRegex(), $uri, $matches)) {
                $parameters = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                return $route->setParameters($parameters);
            }
        }

        return null;
    }

    /**
     * Get all routes.
     *
     * @return array<string, array<Route>>
     */
    public function all(): array
    {
        return $this->routes;
    }
}