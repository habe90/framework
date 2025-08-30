<?php

namespace TrueFrame\Support;

use Dotenv\Dotenv;

class Env
{
    /**
     * The loaded environment variables.
     *
     * @var array<string, string|null>
     */
    protected static array $variables = [];

    /**
     * Load environment variables from a .env file.
     *
     * @param string $path The directory where the .env file is located.
     * @param string $file The name of the .env file (default: '.env').
     * @return void
     */
    public static function load(string $path, string $file = '.env'): void
    {
        if (!file_exists($path . DIRECTORY_SEPARATOR . $file)) {
            return;
        }

        $dotenv = Dotenv::createImmutable($path, $file);
        static::$variables = $dotenv->load();
    }

    /**
     * Get the value of an environment variable.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? static::$variables[$key] ?? false;

        if ($value === false) {
            return $default;
        }

        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'empty':
            case '(empty)':
                return '';
            case 'null':
            case '(null)':
                return null;
        }

        if (preg_match('/\A([\'"])(.*)\1\z/', $value, $matches)) {
            return $matches[2];
        }

        return $value;
    }
}