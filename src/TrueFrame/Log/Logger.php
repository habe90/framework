<?php

namespace TrueFrame\Log;

use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger as Monolog;
use TrueFrame\Config\Repository;
use InvalidArgumentException;

class Logger
{
    /**
     * The Monolog logger instance.
     *
     * @var Monolog
     */
    protected Monolog $logger;

    /**
     * Create a new Logger instance.
     *
     * @param Repository $config
     * @return void
     */
    public function __construct(Repository $config)
    {
        $this->logger = new Monolog($config->get('app.name', 'TrueFrame'));
        $this->configureHandler($config);
    }

    /**
     * Configure the Monolog handler.
     *
     * @param Repository $config
     * @return void
     */
    protected function configureHandler(Repository $config): void
    {
        $channel = $config->get('log_channel', 'daily');
        $path = $config->get('log_path', storage_path('logs/trueframe.log'));

        switch ($channel) {
            case 'single':
                $handler = new StreamHandler($path, Monolog::DEBUG);
                break;
            case 'daily':
                $handler = new RotatingFileHandler($path, 7, Monolog::DEBUG); // Rotate daily, keep 7 files
                break;
            default:
                throw new InvalidArgumentException("Invalid log channel [{$channel}].");
        }

        $this->logger->pushHandler($handler);
    }

    /**
     * Log a debug message.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function debug(string $message, array $context = []): void
    {
        $this->logger->debug($message, $context);
    }

    /**
     * Log an info message.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function info(string $message, array $context = []): void
    {
        $this->logger->info($message, $context);
    }

    /**
     * Log a warning message.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function warning(string $message, array $context = []): void
    {
        $this->logger->warning($message, $context);
    }

    /**
     * Log an error message.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function error(string $message, array $context = []): void
    {
        $this->logger->error($message, $context);
    }

    /**
     * Log a critical message.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function critical(string $message, array $context = []): void
    {
        $this->logger->critical($message, $context);
    }

    /**
     * Log an alert message.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function alert(string $message, array $context = []): void
    {
        $this->logger->alert($message, $context);
    }

    /**
     * Log an emergency message.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function emergency(string $message, array $context = []): void
    {
        $this->logger->emergency($message, $context);
    }

    /**
     * Get the underlying Monolog instance.
     *
     * @return Monolog
     */
    public function getMonolog(): Monolog
    {
        return $this->logger;
    }
}