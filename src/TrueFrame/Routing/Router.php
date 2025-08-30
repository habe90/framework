<?php

namespace TrueFrame\Routing;

use Closure;
use TrueFrame\Application;

class Router
{
    /**
     * The application instance.
     *
     * @var Application
     */
    protected Application $app;

    /**
     * The route collection instance.
     *
     * @var RouteCollection
     */
    protected RouteCollection $routes;

    /**
     * The currently active route group attributes.
     *
     * @var array
     */
    protected array $groupStack = [];

    /**
     * Create a new Router instance.
     *
     * @param Application $app
     * @return void
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->routes = new RouteCollection();
    }

    /**
     * Register a GET route with the router.
     *
     * @param string $uri
     * @param mixed $action
     * @return Route
     */
    public function get(string $uri, mixed $action): Route
    {
        return $this->addRoute('GET', $uri, $action);
    }

    /**
     * Register a POST route with the router.
     *
     * @param string $uri
     * @param mixed $action
     * @return Route
     */
    public function post(string $uri, mixed $action): Route
    {
        return $this->addRoute('POST', $uri, $action);
    }

    /**
     * Register a PUT route with the router.
     *
     * @param string $uri
     * @param mixed $action
     * @return Route
     */
    public function put(string $uri, mixed $action): Route
    {
        return $this->addRoute('PUT', $uri, $action);
    }

    /**
     * Register a PATCH route with the router.
     *
     * @param string $uri
     * @param mixed $action
     * @return Route
     */
    public function patch(string $uri, mixed $action): Route
    {
        return $this->addRoute('PATCH', $uri, $action);
    }

    /**
     * Register a DELETE route with the router.
     *
     * @param string $uri
     * @param mixed $action
     * @return Route
     */
    public function delete(string $uri, mixed $action): Route
    {
        return $this->addRoute('DELETE', $uri, $action);
    }

    /**
     * Register a new route with the given HTTP methods.
     *
     * @param string|array $methods
     * @param string $uri
     * @param mixed $action
     * @return Route
     */
    protected function addRoute(string|array $methods, string $uri, mixed $action): Route
    {
        $uri = $this->mergeGroupPrefix($uri);
        $route = new Route($methods, $uri, $action);

        $this->mergeGroupMiddleware($route);

        return $this->routes->add($route);
    }

    /**
     * Create a route group with shared attributes.
     *
     * @param array $attributes
     * @param Closure $callback
     * @return void
     */
    public function group(array $attributes, Closure $callback): void
    {
        $this->updateGroupStack($attributes);

        $this->app->call($callback);

        array_pop($this->groupStack);
    }

    /**
     * Update the group stack with new attributes.
     *
     * @param array $attributes
     * @return void
     */
    protected function updateGroupStack(array $attributes): void
    {
        if (!empty($this->groupStack)) {
            $last = end($this->groupStack);
            $attributes = array_merge_recursive($last, $attributes);
        }

        $this->groupStack[] = $attributes;
    }

    /**
     * Merge the group prefix into the URI.
     *
     * @param string $uri
     * @return string
     */
    protected function mergeGroupPrefix(string $uri): string
    {
        if (empty($this->groupStack)) {
            return $uri;
        }

        $last = end($this->groupStack);

        if (isset($last['prefix'])) {
            return trim($last['prefix'], '/') . '/' . trim($uri, '/');
        }

        return $uri;
    }

    /**
     * Merge the group middleware into the route.
     *
     * @param Route $route
     * @return void
     */
    protected function mergeGroupMiddleware(Route $route): void
    {
        if (empty($this->groupStack)) {
            return;
        }

        $last = end($this->groupStack);

        if (isset($last['middleware'])) {
            $route->middleware($last['middleware']);
        }
    }

    /**
     * Get the route collection instance.
     *
     * @return RouteCollection
     */
    public function getRoutes(): RouteCollection
    {
        return $this->routes;
    }
}