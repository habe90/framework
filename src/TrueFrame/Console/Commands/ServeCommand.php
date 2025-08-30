<?php

namespace TrueFrame\Console\Commands;

use TrueFrame\Console\Command;
use Exception;

class ServeCommand extends Command
{
    protected string $signature = 'serve';
    protected string $description = 'Serve the application on the PHP development server.';

    public function handle(): int
    {
        $host = '127.0.0.1';
        $port = '8000';

        // Parse arguments for host and port if provided
        $args = $this->argument();
        foreach ($args as $arg) {
            if (str_starts_with($arg, '--host=')) {
                $host = substr($arg, 7);
            } elseif (str_starts_with($arg, '--port=')) {
                $port = substr($arg, 7);
            }
        }

        $docroot = $this->app->basePath('public');

        if (!is_dir($docroot)) {
            $this->error("Public directory not found at: {$docroot}");
            return 1;
        }

        $this->info("TrueFrame development server started on http://{$host}:{$port}");
        $this->info("Press Ctrl+C to stop the server.");

        // Ensure PHP_BINARY is correctly defined (it usually is)
        $command = escapeshellarg(PHP_BINARY) . " -S {$host}:{$port} -t " . escapeshellarg($docroot);

        // Execute the PHP built-in server command
        passthru($command);

        return 0;
    }
}