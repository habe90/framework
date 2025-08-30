<?php

namespace TrueFrame\Console\Commands;

use TrueFrame\Console\Command;
use RuntimeException;

class KeyGenerateCommand extends Command
{
    protected string $signature = 'key:generate';
    protected string $description = 'Set the application key.';

    public function handle(): int
    {
        $key = $this->generateRandomKey();

        $path = $this->app->basePath('.env');

        if (!file_exists($path)) {
            $this->error(".env file not found. Please create one from .env.example.");
            return 1;
        }

        if (str_contains(file_get_contents($path), 'APP_KEY=')) {
            file_put_contents($path, preg_replace(
                '/^APP_KEY=.*$/m',
                'APP_KEY=' . $key,
                file_get_contents($path)
            ));
        } else {
            file_put_contents($path, file_get_contents($path) . "\nAPP_KEY=" . $key);
        }

        $this->info("Application key [{$key}] set successfully.");

        return 0;
    }

    /**
     * Generate a random key for the application.
     *
     * @return string
     * @throws RuntimeException
     */
    protected function generateRandomKey(): string
    {
        return 'base64:' . base64_encode(random_bytes(32));
    }
}