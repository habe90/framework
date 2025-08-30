<?php

namespace TrueFrame\View;

use TrueFrame\Application;
use TrueFrame\Config\Repository;
use InvalidArgumentException;
use Throwable;

class View
{
    /**
     * The application instance.
     *
     * @var Application
     */
    protected Application $app;

    /**
     * The view paths.
     *
     * @var array
     */
    protected array $paths;

    /**
     * The compiled view path.
     *
     * @var string
     */
    protected string $compiledPath;

    /**
     * The compiler instance.
     *
     * @var Compiler
     */
    public Compiler $compiler; // Made public for easier access in Compiler

    /**
     * Create a new view factory instance.
     *
     * @param Application $app
     * @param Repository $config
     * @param Compiler $compiler
     */
    public function __construct(Application $app, Repository $config, Compiler $compiler)
    {
        $this->app = $app;
        $this->paths = $config->get('view.paths', []);
        $this->compiledPath = $config->get('view.compiled');
        $this->compiler = $compiler;

        // Set the view factory on the compiler for @include directives
        $this->compiler->setViewFactory($this);
    }

    /**
     * Get the evaluated view contents for the given view.
     *
     * @param string $view The view name (e.g., 'home', 'layouts.app').
     * @param array $data
     * @return string
     * @throws InvalidArgumentException
     * @throws Throwable
     */
    public function make(string $view, array $data = []): string
    {
        $path = $this->findView($view);

        if (!$path) {
            throw new InvalidArgumentException("View [{$view}] not found.");
        }

        $compiledPath = $this->compiler->compile($path);

        return $this->render($compiledPath, $data);
    }

    /**
     * Find the given view in the registered paths.
     *
     * @param string $view
     * @return string|null
     */
    protected function findView(string $view): ?string
    {
        $view = str_replace('.', DIRECTORY_SEPARATOR, $view);

        foreach ($this->paths as $path) {
            $fullPath = $path . DIRECTORY_SEPARATOR . $view . '.tf.php';
            if (file_exists($fullPath)) {
                return $fullPath;
            }
        }

        return null;
    }

    /**
     * Get the evaluated contents of the view.
     *
     * @param string $compiledPath
     * @param array $data
     * @return string
     * @throws Throwable
     */
    protected function render(string $compiledPath, array $data): string
    {
        // Pass the compiler instance as $__env for directives like @yield, @section, @include
        $__env = $this->compiler;

        // Extract data for easy access in the view
        extract($data);

        // Start output buffering
        ob_start();

        // Include the compiled view file
        try {
            include $compiledPath;
        } catch (Throwable $e) {
            ob_get_clean(); // Clean the buffer on error
            throw $e;
        }

        // Get the buffered content and clean the buffer
        return ob_get_clean();
    }
}