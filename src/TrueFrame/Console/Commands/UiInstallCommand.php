<?php

namespace TrueFrame\Console\Commands;

use TrueFrame\Console\Command;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class UiInstallCommand extends Command
{
    protected string $signature = 'ui:install';
    protected string $description = 'Install UI scaffolds (e.g., Tailwind CSS).';

    public function handle(): int
    {
        $stack = $this->argument(0);

        if (empty($stack)) {
            $this->error('Please specify a UI stack to install (e.g., "tailwind").');
            return 1;
        }

        switch (strtolower($stack)) {
            case 'tailwind':
                return $this->installTailwind();
            default:
                $this->error("UI stack '{$stack}' not supported.");
                return 1;
        }
    }

    /**
     * Install the Tailwind CSS scaffold.
     *
     * @return int
     */
    protected function installTailwind(): int
    {
        $this->info("Installing Tailwind CSS UI scaffold...");

        // 1. Copy Tailwind config files
        $this->copyStub('tailwind.config.js', 'tailwind.config.js');
        $this->copyStub('postcss.config.js', 'postcss.config.js');

        // 2. Update package.json (if it exists)
        $this->updatePackageJson();

        // 3. Ensure resources/css/app.css has @tailwind directives
        $this->ensureTailwindCss();

        // 4. Copy minimal auth views
        $this->copyStub('views/auth/login.tf.php', 'resources/views/auth/login.tf.php');
        $this->copyStub('views/auth/register.tf.php', 'resources/views/auth/register.tf.php');
        $this->copyStub('views/layouts/app.tf.php', 'resources/views/layouts/app.tf.php'); // Ensure layout has build/app.css

        $this->info("Tailwind CSS UI scaffold installed successfully.");
        $this->warn("Remember to run `npm install` and then build your assets (e.g., `npx tailwindcss -i ./resources/css/app.css -o ./public/build/app.css --watch`).");

        return 0;
    }

    /**
     * Copy a stub file to the project.
     *
     * @param string $stubName
     * @param string $destinationPath
     * @return void
     */
    protected function copyStub(string $stubName, string $destinationPath): void
    {
        $source = __DIR__ . "/stubs/{$stubName}";
        $destination = $this->app->basePath($destinationPath);

        if (!file_exists(dirname($destination))) {
            mkdir(dirname($destination), 0755, true);
        }

        if (!file_exists($source)) {
            $this->error("Stub file not found: {$source}");
            return;
        }

        file_put_contents($destination, file_get_contents($source));
        $this->line("Copied: {$destinationPath}");
    }

    /**
     * Update package.json with Tailwind and PostCSS scripts.
     *
     * @return void
     */
    protected function updatePackageJson(): void
    {
        $path = $this->app->basePath('package.json');
        if (!file_exists($path)) {
            $this->warn("package.json not found. Please create one or run `npm init -y`.");
            return;
        }

        $content = json_decode(file_get_contents($path), true);

        $content['devDependencies']['tailwindcss'] = '^3.0';
        $content['devDependencies']['postcss'] = '^8.0';
        $content['devDependencies']['autoprefixer'] = '^10.0';

        $content['scripts']['dev'] = 'npx tailwindcss -i ./resources/css/app.css -o ./public/build/app.css --watch';
        $content['scripts']['build'] = 'npx tailwindcss -i ./resources/css/app.css -o ./public/build/app.css --minify';

        file_put_contents($path, json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->line("Updated package.json.");
    }

    /**
     * Ensure resources/css/app.css has Tailwind directives.
     *
     * @return void
     */
    protected function ensureTailwindCss(): void
    {
        $path = $this->app->basePath('resources/css/app.css');
        if (!file_exists($path)) {
            $this->copyStub('resources/css/app.css', 'resources/css/app.css');
            return;
        }

        $content = file_get_contents($path);
        if (!str_contains($content, '@tailwind base;')) {
            $tailwindDirectives = "@tailwind base;\n@tailwind components;\n@tailwind utilities;\n\n" . $content;
            file_put_contents($path, $tailwindDirectives);
            $this->line("Added Tailwind directives to resources/css/app.css.");
        }
    }
}