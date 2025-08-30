<?php

namespace TrueFrame;

use Closure;
use TrueFrame\Config\Repository;
use TrueFrame\Container\Container;
use TrueFrame\Support\ServiceProvider;
use TrueFrame\Routing\Router; // Added for alias

class Application extends Container
{
    /**
     * The TrueFrame framework version.
     *
     * @var string
     */
    const VERSION = '0.1.0';

    /**
     * The base path of the TrueFrame installation.
     *
     * @var string
     */
    protected string $basePath;

    /**
     * The application's service providers.
     *
     * @var array
     */
    protected array $serviceProviders = [];

    /**
     * The booted service providers.
     *
     * @var array
     */
    protected array $bootedProviders = [];

    /**
     * The application instance.
     *
     * @var static
     */
    protected static $instance;

    /**
     * Create a new TrueFrame application instance.
     *
     * @param string|null $basePath
     * @return void
     */
    public function __construct(string $basePath = null)
    {
        if ($basePath) {
            $this->setBasePath($basePath);
        }

        static::setInstance($this);

        $this->registerBaseBindings();
        $this->registerBaseServiceProviders();
        $this->registerCoreContainerAliases();
    }

    /**
     * Get the version number of the application.
     *
     * @return string
     */
    public function version(): string
    {
        return static::VERSION;
    }

    /**
     * Set the base path for the application.
     *
     * @param string $basePath
     * @return $this
     */
    public function setBasePath(string $basePath): static
    {
        $this->basePath = rtrim($basePath, '\/');
        return $this;
    }

    /**
     * Get the base path of the application.
     *
     * @param string $path
     * @return string
     */
    public function basePath(string $path = ''): string
    {
        return $this->basePath . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    /**
     * Register the basic bindings into the container.
     *
     * @return void
     */
    protected function registerBaseBindings(): void
    {
        $this->instance('app', $this);
        $this->instance(Application::class, $this);

        // Bind request as singleton, it will be captured in public/index.php
        $this->singleton(\TrueFrame\Http\Request::class, fn() => \TrueFrame\Http\Request::capture());
    }

    /**
     * Register the core service providers.
     *
     * @return void
     */
    protected function registerBaseServiceProviders(): void
    {
        // Add core providers here if needed, or rely on bootstrap/app.php
    }

    /**
     * Register a service provider with the application.
     *
     * @param string|ServiceProvider $provider
     * @param bool $force
     * @return ServiceProvider
     */
    public function register(string|ServiceProvider $provider, bool $force = false): ServiceProvider
    {
        if ($existing = $this->getProvider($provider) && !$force) {
            return $existing;
        }

        if (is_string($provider)) {
            $provider = $this->resolveProvider($provider);
        }

        $provider->register();

        if (property_exists($provider, 'bindings')) {
            foreach ($provider->bindings as $key => $value) {
                $this->bind($key, $value);
            }
        }

        if (property_exists($provider, 'singletons')) {
            foreach ($provider->singletons as $key => $value) {
                $this->singleton($key, $value);
            }
        }

        $this->serviceProviders[] = $provider;

        if ($this->isBooted()) {
            $this->bootProvider($provider);
        }

        return $provider;
    }

    /**
     * Get the registered service provider instance if it exists.
     *
     * @param string|ServiceProvider $provider
     * @return ServiceProvider|null
     */
    public function getProvider(string|ServiceProvider $provider): ?ServiceProvider
    {
        $name = is_string($provider) ? $provider : get_class($provider);

        foreach ($this->serviceProviders as $p) {
            if ($p instanceof $name) {
                return $p;
            }
        }

        return null;
    }

    /**
     * Resolve a service provider instance from the class name.
     *
     * @param string $provider
     * @return ServiceProvider
     */
    public function resolveProvider(string $provider): ServiceProvider
    {
        return new $provider($this);
    }

    /**
     * Boot the given service provider.
     *
     * @param ServiceProvider $provider
     * @return mixed
     */
    protected function bootProvider(ServiceProvider $provider): mixed
    {
        if (method_exists($provider, 'boot') && !in_array($provider, $this->bootedProviders)) {
            $this->call([$provider, 'boot']);
            $this->bootedProviders[] = $provider;
        }
        return $provider;
    }

    /**
     * Boot all of the application's service providers.
     *
     * @return void
     */
    public function boot(): void
    {
        foreach ($this->serviceProviders as $provider) {
            $this->bootProvider($provider);
        }
    }

    /**
     * Determine if the application has been booted.
     *
     * @return bool
     */
    public function isBooted(): bool
    {
        return count($this->bootedProviders) > 0;
    }

    /**
     * Set the globally available instance of the container.
     *
     * @return static
     */
    public static function getInstance(): static
    {
        return static::$instance;
    }

    /**
     * Set the shared instance of the container.
     *
     * @param \TrueFrame\Container\Container|null $container
     * @return static
     */
    public static function setInstance(?Container $container = null): static
    {
        return static::$instance = $container;
    }

    /**
     * Get the current application environment.
     *
     * @return string
     */
    public function environment(): string
    {
        return $this->make(Repository::class)->get('app.env');
    }

    /**
     * Determine if the application is in the local environment.
     *
     * @return bool
     */
    public function isLocal(): bool
    {
        return $this->environment() === 'local';
    }

    /**
     * Determine if the application is in the production environment.
     *
     * @return bool
     */
    public function isProduction(): bool
    {
        return $this->environment() === 'production';
    }

    /**
     * Register the core class aliases.
     *
     * @return void
     */
    public function registerCoreContainerAliases(): void
    {
        $this->alias('router', Router::class);
        $this->alias('config', Repository::class);
        $this->alias('session', \TrueFrame\Session\SessionManager::class);
        $this->alias('log', \TrueFrame\Log\Logger::class);
        $this->alias('view.factory', \TrueFrame\View\View::class);
    }
}