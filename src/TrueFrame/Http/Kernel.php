<?php

namespace TrueFrame\Http;

use TrueFrame\Application;
use TrueFrame\Http\Middleware\MiddlewareInterface;
use TrueFrame\Routing\Router;
use TrueFrame\Exceptions\NotFoundException;
use Throwable;

class Kernel
{
    /**
     * The application instance.
     *
     * @var Application
     */
    protected Application $app;

    /**
     * The router instance.
     *
     * @var Router
     */
    protected Router $router;

    /**
     * The array of global HTTP middleware stack.
     *
     * @var array<class-string<MiddlewareInterface>>
     */
    protected array $middleware = [
        // \App\Http\Middleware\StartSession::class, // Moved to groups
        // \App\Http\Middleware\CsrfMiddleware::class, // Moved to groups
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected array $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\StartSession::class,
            \App\Http\Middleware\CsrfMiddleware::class,
        ],
        'api' => [
            // \App\Http\Middleware\AuthMiddleware::class, // API token based auth
        ],
    ];

    /**
     * The application's route middleware.
     *
     * @var array<string, class-string<MiddlewareInterface>>
     */
    protected array $routeMiddleware = [
        'auth' => \App\Http\Middleware\AuthMiddleware::class,
        // 'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        // 'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
        // 'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        // 'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
    ];

    /**
     * Create a new HTTP kernel instance.
     *
     * @param Application $app
     * @param Router $router
     * @return void
     */
    public function __construct(Application $app, Router $router)
    {
        $this->app = $app;
        $this->router = $router;
    }

    /**
     * Handle an incoming HTTP request.
     *
     * @param Request $request
     * @return Response
     * @throws Throwable
     */
    public function handle(Request $request): Response
    {
        try {
            // Find the matching route
            $route = $this->router->getRoutes()->match($request->method(), $request->path());

            if (!$route) {
                throw new NotFoundException();
            }

            // Prepare middleware stack for the route
            $middleware = $this->gatherRouteMiddleware($route);

            // Add global middleware to the beginning of the stack
            $middleware = array_merge($this->middleware, $middleware);

            // Create a pipeline to process the request
            $response = (new \TrueFrame\Http\Pipeline($this->app))
                ->send($request)
                ->through($middleware)
                ->then(function ($request) use ($route) {
                    return $route->run($request);
                });

            return $response;

        } catch (NotFoundException $e) {
            return response('Not Found', 404)->header('Content-Type', 'text/html');
        } catch (Throwable $e) {
            throw $e; // Let the global exception handler deal with it
        }
    }

    /**
     * Gather the route middleware for the given route.
     *
     * @param \TrueFrame\Routing\Route $route
     * @return array
     */
    protected function gatherRouteMiddleware(\TrueFrame\Routing\Route $route): array
    {
        $middleware = [];

        foreach ($route->getMiddleware() as $name) {
            // Check if it's a middleware group (e.g., 'web', 'api')
            if (isset($this->middlewareGroups[$name])) {
                $middleware = array_merge($middleware, $this->middlewareGroups[$name]);
            }
            // Check if it's a named route middleware (e.g., 'auth')
            elseif (isset($this->routeMiddleware[$name])) {
                $middleware[] = $this->routeMiddleware[$name];
            }
            // Assume it's a fully qualified class name
            else {
                $middleware[] = $name;
            }
        }

        return array_unique($middleware);
    }

    /**
     * Perform any final actions for the request lifecycle.
     *
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function terminate(Request $request, Response $response): void
    {
        // For now, nothing specific to terminate.
        // In a real app, this is where you might flush session, close DB connections, etc.
    }
}