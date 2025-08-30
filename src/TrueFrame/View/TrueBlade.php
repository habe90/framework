<?php

namespace TrueFrame\View;

use Exception;

/**
 * A very basic templating engine placeholder.
 * This is *not* a full Blade clone, but demonstrates the concept of a view renderer.
 * It simply includes PHP files.
 */
class TrueBlade
{
    protected string $viewPath;
    protected string $cachePath;

    public function __construct(string $viewPath, string $cachePath)
    {
        $this->viewPath = rtrim($viewPath, '/\\');
        $this->cachePath = rtrim($cachePath, '/\\');

        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0777, true);
        }
    }

    /**
     * Renders a view file with the given data.
     *
     * @param string $view The view file name (e.g., 'home.index').
     * @param array $data Data to pass to the view.
     * @return string The rendered HTML content.
     * @throws Exception If the view file is not found.
     */
    public function render(string $view, array $data = []): string
    {
        $viewFile = $this->resolveViewPath($view);

        if (!file_exists($viewFile)) {
            throw new Exception("View file not found: {$viewFile}");
        }

        // For a true Blade-like engine, this is where compilation would happen.
        // For this basic version, we just include the PHP file.
        // A simple compilation step might involve replacing {{ $var }} with <?php echo $var; ?>
        // and @if/@endif with <?php if(...): ?> <?php endif; ?>

        ob_start();
        extract($data); // Extract data into the local symbol table for the view
        require $viewFile; // Include the view file
        return ob_get_clean();
    }

    /**
     * Resolves the full path to a view file.
     *
     * @param string $view
     * @return string
     */
    protected function resolveViewPath(string $view): string
    {
        // Convert dot notation to directory path and add .php extension
        return $this->viewPath . DIRECTORY_SEPARATOR . str_replace('.', DIRECTORY_SEPARATOR, $view) . '.php';
    }

    /**
     * A very basic compile method (placeholder for actual Blade compilation).
     *
     * @param string $templateContent
     * @return string
     */
    protected function compile(string $templateContent): string
    {
        // This is where you'd implement logic to convert Blade-like syntax
        // For example:
        // $templateContent = preg_replace('/\{\{\s*(.*?)\s*\}\}/', '<?php echo htmlspecialchars($1); ?>', $templateContent);
        // $templateContent = preg_replace('/@if\((.*?)\)/', '<?php if($1): ?>', $templateContent);
        // $templateContent = str_replace('@endif', '<?php endif; ?>', $templateContent);

        return $templateContent;
    }
}