<?php

namespace TrueFrame\AI;

interface ScaffolderInterface
{
    /**
     * Scaffold a resource (model, controller, migration, views, routes).
     *
     * @param string $name The name of the resource (e.g., 'Post').
     * @param array<string, string> $fields Associative array of field names and types (e.g., ['title' => 'string', 'body' => 'text']).
     * @param array<string, mixed> $flags Command line options/flags (e.g., ['--crud' => true, '--api' => true]).
     * @return void
     */
    public function scaffold(string $name, array $fields, array $flags): void;
}