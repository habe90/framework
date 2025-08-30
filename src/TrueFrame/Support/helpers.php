<?php

use TrueFrame\Application;
use TrueFrame\Config\Repository;
use TrueFrame\Http\Request;
use TrueFrame\Http\Response;
use TrueFrame\Log\Logger;
use TrueFrame\Routing\Router;
use TrueFrame\Session\SessionManager;
use TrueFrame\View\View;

if (!function_exists('app')) {
    /**
     * Get the TrueFrame application instance.
     *
     * @param string|null $abstract
     * @param array $parameters
     * @return mixed|\TrueFrame\Application
     */
    function app(string $abstract = null, array $parameters = [])
    {
        if (is_null($abstract)) {
            return Application::getInstance();
        }

        return Application::getInstance()->make($abstract, $parameters);
    }
}

if (!function_exists('base_path')) {
    /**
     * Get the base path of the TrueFrame installation.
     *
     * @param string $path
     * @return string
     */
    function base_path(string $path = ''): string
    {
        return app()->basePath($path);
    }
}

if (!function_exists('config_path')) {
    /**
     * Get the configuration path.
     *
     * @param string $path
     * @return string
     */
    function config_path(string $path = ''): string
    {
        return base_path('config' . ($path ? DIRECTORY_SEPARATOR . $path : $path));
    }
}

if (!function_exists('database_path')) {
    /**
     * Get the database path.
     *
     * @param string $path
     * @return string
     */
    function database_path(string $path = ''): string
    {
        return base_path('database' . ($path ? DIRECTORY_SEPARATOR . $path : $path));
    }
}

if (!function_exists('resource_path')) {
    /**
     * Get the resources path.
     *
     * @param string $path
     * @return string
     */
    function resource_path(string $path = ''): string
    {
        return base_path('resources' . ($path ? DIRECTORY_SEPARATOR . $path : $path));
    }
}

if (!function_exists('storage_path')) {
    /**
     * Get the storage path.
     *
     * @param string $path
     * @return string
     */
    function storage_path(string $path = ''): string
    {
        return base_path('storage' . ($path ? DIRECTORY_SEPARATOR . $path : $path));
    }
}

if (!function_exists('env')) {
    /**
     * Get the value of an environment variable.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function env(string $key, mixed $default = null): mixed
    {
        return \TrueFrame\Support\Env::get($key, $default);
    }
}

if (!function_exists('config')) {
    /**
     * Get / set the specified configuration value.
     *
     * @param array|string|null $key
     * @param mixed $default
     * @return mixed|\TrueFrame\Config\Repository
     */
    function config(array|string $key = null, mixed $default = null): mixed
    {
        if (is_null($key)) {
            return app('config');
        }

        if (is_array($key)) {
            return app('config')->set($key);
        }

        return app('config')->get($key, $default);
    }
}

if (!function_exists('view')) {
    /**
     * Get the evaluated view contents for the given view.
     *
     * @param string $view
     * @param array $data
     * @return \TrueFrame\Http\Response
     */
    function view(string $view, array $data = []): Response
    {
        return (new Response(app(View::class)->make($view, $data)))->header('Content-Type', 'text/html');
    }
}

if (!function_exists('response')) {
    /**
     * Create a new response instance.
     *
     * @param string $content
     * @param int $status
     * @param array $headers
     * @return \TrueFrame\Http\Response
     */
    function response(string $content = '', int $status = 200, array $headers = []): Response
    {
        return new Response($content, $status, $headers);
    }
}

if (!function_exists('redirect')) {
    /**
     * Create a new redirect response.
     *
     * @param string $url
     * @param int $status
     * @param array $headers
     * @return \TrueFrame\Http\Response
     */
    function redirect(string $url, int $status = 302, array $headers = []): Response
    {
        return (new Response('', $status, array_merge($headers, ['Location' => $url])));
    }
}

if (!function_exists('request')) {
    /**
     * Get the current request instance.
     *
     * @return \TrueFrame\Http\Request
     */
    function request(): Request
    {
        return app(Request::class);
    }
}

if (!function_exists('session')) {
    /**
     * Get the session manager instance.
     *
     * @return \TrueFrame\Session\SessionManager
     */
    function session(): SessionManager
    {
        return app(SessionManager::class);
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Get the CSRF token value.
     *
     * @return string
     */
    function csrf_token(): string
    {
        $session = session();
        if (!$session->has('_token')) {
            $session->put('_token', bin2hex(random_bytes(32)));
        }
        return $session->get('_token');
    }
}

if (!function_exists('logger')) {
    /**
     * Get the logger instance.
     *
     * @return \TrueFrame\Log\Logger
     */
    function logger(): Logger
    {
        return app(Logger::class);
    }
}

if (!function_exists('old')) {
    /**
     * Retrieve an old input item from the session.
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    function old(?string $key = null, mixed $default = null): mixed
    {
        if (is_null($key)) {
            return session()->getOldInput();
        }
        return session()->getOldInput($key, $default);
    }
}

if (!function_exists('errors')) {
    /**
     * Retrieve validation errors from the session.
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    function errors(?string $key = null, mixed $default = null): mixed
    {
        $allErrors = session()->getFlash('errors', []);

        if (is_null($key)) {
            return $allErrors;
        }

        return $allErrors[$key] ?? $default;
    }
}