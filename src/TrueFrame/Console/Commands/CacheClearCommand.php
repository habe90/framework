<?php

namespace TrueFrame\Console\Commands;

use TrueFrame\Console\Command;

class CacheClearCommand extends Command
{
    protected string $signature = 'cache:clear';
    protected string $description = 'Clear the application cache and compiled views.';

    public function handle(): int
    {
        $this->info('Clearing cache...');

        $cachePath = storage_path('cache');
        $viewsPath = storage_path('views');
        $logPath = storage_path('logs'); // Clearing logs too? User requested cache:clear (briše storage/cache i compiled views)

        $this->deleteDirectory($cachePath);
        $this->deleteDirectory($viewsPath);

        $this->info('Application cache and compiled views cleared successfully.');
        return 0;
    }

    /**
     * Delete a directory and its contents.
     *
     * @param string $dir
     * @return void
     */
    protected function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST) as $path) {
            $path->isFile() ? unlink($path->getPathname()) : rmdir($path->getPathname());
        }
    }
}