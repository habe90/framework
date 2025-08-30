<?php

namespace TrueFrame\Http;

use TrueFrame\Session\SessionManager;

class Request
{
    /**
     * The captured request instance.
     *
     * @var static|null
     */
    protected static ?self $instance = null;

    /**
     * The request method.
     *
     * @var string
     */
    protected string $method;

    /**
     * The request URI.
     *
     * @var string
     */
    protected string $uri;

    /**
     * The request headers.
     *
     * @var array
     */
    protected array $headers;

    /**
     * The request input (GET, POST).
     *
     * @var array
     */
    protected array $input;

    /**
     * The request files.
     *
     * @var array
     */
    protected array $files;

    /**
     * The server parameters.
     *
     * @var array
     */
    protected array $server;

    /**
     * The cookies.
     *
     * @var array
     */
    protected array $cookies;

    /**
     * Create a new Request instance.
     *
     * @param string $method
     * @param string $uri
     * @param array $headers
     * @param array $input
     * @param array $files
     * @param array $server
     * @param array $cookies
     */
    public function __construct(
        string $method,
        string $uri,
        array $headers,
        array $input,
        array $files,
        array $server,
        array $cookies
    ) {
        $this->method = $method;
        $this->uri = $uri;
        $this->headers = $headers;
        $this->input = $input;
        $this->files = $files;
        $this->server = $server;
        $this->cookies = $cookies;
    }

    /**
     * Capture the current HTTP request.
     *
     * @return static
     */
    public static function capture(): static
    {
        if (static::$instance) {
            return static::$instance;
        }

        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $headers = getallheaders() ?: [];
        $input = $_GET + $_POST; // Merge GET and POST data
        $files = $_FILES;
        $server = $_SERVER;
        $cookies = $_COOKIE;

        // Handle _method spoofing for PUT/PATCH/DELETE
        if (isset($input['_method']) && in_array(strtoupper($input['_method']), ['PUT', 'PATCH', 'DELETE'])) {
            $method = strtoupper($input['_method']);
            unset($input['_method']);
        }

        return static::$instance = new static($method, $uri, $headers, $input, $files, $server, $cookies);
    }

    /**
     * Get the request method.
     *
     * @return string
     */
    public function method(): string
    {
        return $this->method;
    }

    /**
     * Get the request path.
     *
     * @return string
     */
    public function path(): string
    {
        return $this->uri;
    }

    /**
     * Get an input value from the request.
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function input(?string $key = null, mixed $default = null): mixed
    {
        if (is_null($key)) {
            return $this->all();
        }
        return $this->input[$key] ?? $default;
    }

    /**
     * Get all input (GET and POST) from the request.
     *
     * @return array
     */
    public function all(): array
    {
        return $this->input;
    }

    /**
     * Get a header from the request.
     *
     * @param string $key
     * @param mixed $default
     * @return string|array|null
     */
    public function header(string $key, mixed $default = null): string|array|null
    {
        $key = str_replace('-', '_', strtoupper($key));
        return $this->headers[$key] ?? $this->headers['HTTP_' . $key] ?? $default;
    }

    /**
     * Get a server parameter from the request.
     *
     * @param string $key
     * @param mixed $default
     * @return string|null
     */
    public function server(string $key, mixed $default = null): ?string
    {
        return $this->server[$key] ?? $default;
    }

    /**
     * Get a cookie from the request.
     *
     * @param string $key
     * @param mixed $default
     * @return string|null
     */
    public function cookie(string $key, mixed $default = null): ?string
    {
        return $this->cookies[$key] ?? $default;
    }

    /**
     * Get the session manager instance.
     *
     * @return SessionManager
     */
    public function session(): SessionManager
    {
        return app(SessionManager::class);
    }

    /**
     * Check if the request is an AJAX request.
     *
     * @return bool
     */
    public function ajax(): bool
    {
        return $this->header('X-Requested-With') === 'XMLHttpRequest';
    }

    /**
     * Check if the request expects a JSON response.
     *
     * @return bool
     */
    public function expectsJson(): bool
    {
        return str_contains($this->header('Accept') ?? '', '/json');
    }

    /**
     * Get the current request URI without query string.
     *
     * @return string
     */
    public function getUriWithoutQuery(): string
    {
        return strtok($this->server('REQUEST_URI'), '?');
    }

    /**
     * Get the previous URL.
     *
     * @return string|null
     */
    public function previousUrl(): ?string
    {
        return $this->server('HTTP_REFERER');
    }

    /**
     * Check if the request method matches one of the given methods.
     *
     * @param array|string $methods
     * @return bool
     */
    public function isMethod(array|string $methods): bool
    {
        $methods = array_map('strtoupper', (array) $methods);
        return in_array($this->method(), $methods);
    }
}