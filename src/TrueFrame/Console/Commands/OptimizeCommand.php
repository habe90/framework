<?php

namespace TrueFrame\Console\Commands;

use TrueFrame\Console\Command;
use Exception;

class OptimizeCommand extends Command
{
    protected string $signature = 'optimize';
    protected string $description = 'Cache the framework bootstrap files and clear compiled views.';

    public function handle(): int
    {
        $this->info('Optimizing TrueFrame application...');

        try {
            // For MVP, "optimize" means clearing relevant caches.
            // In a full framework, this would involve caching config, routes, views, etc.
            $this->call('cache:clear');

            $this->info('Application optimized successfully.');
            return 0;
        } catch (Exception $e) {
            $this->error("Optimization failed: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Call another console command.
     *
     * @param string $command
     * @param array $args
     * @return int
     */
    protected function call(string $command, array $args = []): int
    {
        $console = $this->app->make(\TrueFrame\Console\Application::class);
        return $console->run(array_merge([$command], $args));
    }
}