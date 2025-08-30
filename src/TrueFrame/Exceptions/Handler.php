<?php

namespace TrueFrame\Exceptions;

use TrueFrame\Application;
use TrueFrame\Http\Request;
use TrueFrame\Http\Response;
use TrueFrame\Log\Logger;
use Throwable;
use ErrorException;

class Handler
{
    /**
     * The application instance.
     *
     * @var Application
     */
    protected Application $app;

    /**
     * The logger instance.
     *
     * @var Logger
     */
    protected Logger $logger;

    /**
     * Create a new exception handler instance.
     *
     * @param Application $app
     * @param Logger $logger
     */
    public function __construct(Application $app, Logger $logger)
    {
        $this->app = $app;
        $this->logger = $logger;
    }

    /**
     * Register the exception / error handlers for the application.
     *
     * @return void
     */
    public function register(): void
    {
        error_reporting(E_ALL);

        set_error_handler([$this, 'handleError']);

        set_exception_handler([$this, 'handleException']);
    }

    /**
     * Convert PHP errors to ErrorException instances.
     *
     * @param int $level
     * @param string $message
     * @param string $file
     * @param int $line
     * @param array $context
     * @return bool
     * @throws ErrorException
     */
    public function handleError(int $level, string $message, string $file = '', int $line = 0, array $context = []): bool
    {
        if (error_reporting() & $level) {
            throw new ErrorException($message, 0, $level, $file, $line);
        }
        return false;
    }

    /**
     * Handle an uncaught exception.
     *
     * @param Throwable $e
     * @return void
     */
    public function handleException(Throwable $e): void
    {
        $this->render(request(), $e)->send();
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param Request $request
     * @param Throwable $e
     * @return Response
     */
    public function render(Request $request, Throwable $e): Response
    {
        $this->report($e);

        if ($this->app->isLocal() && config('app.debug')) {
            return $this->renderExceptionWithDebugPage($e);
        }

        if ($request->expectsJson()) {
            return $this->renderExceptionAsJson($e);
        }

        return $this->renderGenericErrorPage($e);
    }

    /**
     * Report or log an exception.
     *
     * @param Throwable $e
     * @return void
     */
    public function report(Throwable $e): void
    {
        $this->logger->error(
            $e->getMessage(),
            [
                'exception' => get_class($e),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]
        );
    }

    /**
     * Render the exception using a debug page.
     *
     * @param Throwable $e
     * @return Response
     */
    protected function renderExceptionWithDebugPage(Throwable $e): Response
    {
        $title = get_class($e);
        $message = $e->getMessage();
        $file = $e->getFile();
        $line = $e->getLine();
        $trace = explode("\n", $e->getTraceAsString());

        $html = <<<HTML
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error: {$title}</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8f8f8; color: #333; margin: 0; padding: 20px; }
        .container { max-width: 960px; margin: 0 auto; background-color: #fff; border-radius: 8px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05); overflow: hidden; }
        .header { background-color: #dc3545; color: #fff; padding: 20px 30px; border-bottom: 1px solid #c82333; }
        .header h1 { margin: 0; font-size: 28px; font-weight: 600; }
        .header p { margin: 5px 0 0; font-size: 16px; opacity: 0.9; }
        .content { padding: 30px; }
        .section { margin-bottom: 25px; }
        .section-title { font-size: 22px; color: #333; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        pre { background-color: #f4f4f4; border: 1px solid #ddd; border-radius: 4px; padding: 15px; overflow-x: auto; font-size: 14px; line-height: 1.5; color: #555; }
        code { font-family: 'Consolas', 'Monaco', monospace; }
        .file-line { background-color: #e9ecef; border-left: 5px solid #007bff; padding: 10px 15px; margin-bottom: 15px; font-size: 15px; }
        .file-line strong { color: #007bff; }
        .stack-trace ol { list-style: none; padding: 0; margin: 0; }
        .stack-trace li { background-color: #fdfdfd; border: 1px solid #eee; border-radius: 4px; margin-bottom: 10px; padding: 10px 15px; font-size: 14px; color: #666; }
        .stack-trace li:last-child { margin-bottom: 0; }
        .stack-trace li strong { color: #333; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{$title}</h1>
            <p>{$message}</p>
        </div>
        <div class="content">
            <div class="section">
                <div class="file-line">
                    <strong>{$file}</strong> at line <strong>{$line}</strong>
                </div>
            </div>

            <div class="section">
                <h2 class="section-title">Stack Trace</h2>
                <div class="stack-trace">
                    <pre><code>{$e->getTraceAsString()}</code></pre>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
        return new Response($html, 500, ['Content-Type' => 'text/html']);
    }

    /**
     * Render the exception as a JSON response.
     *
     * @param Throwable $e
     * @return Response
     */
    protected function renderExceptionAsJson(Throwable $e): Response
    {
        $status = $e instanceof NotFoundException ? 404 : 500;
        $data = [
            'message' => $e->getMessage(),
            'exception' => get_class($e),
            'code' => $e->getCode(),
        ];

        if ($this->app->isLocal() && config('app.debug')) {
            $data['file'] = $e->getFile();
            $data['line'] = $e->getLine();
            $data['trace'] = explode("\n", $e->getTraceAsString());
        }

        return response()->json($data, $status);
    }

    /**
     * Render a generic error page.
     *
     * @param Throwable $e
     * @return Response
     */
    protected function renderGenericErrorPage(Throwable $e): Response
    {
        $status = $e instanceof NotFoundException ? 404 : 500;
        $message = $e instanceof NotFoundException ? 'Page Not Found.' : 'Whoops, something went wrong.';

        $html = <<<HTML
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error {$status}</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8f8f8; color: #333; margin: 0; padding: 20px; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .error-container { text-align: center; background-color: #fff; padding: 40px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05); }
        .error-container h1 { font-size: 80px; margin: 0; color: #dc3545; }
        .error-container h2 { font-size: 24px; margin-top: 10px; color: #555; }
        .error-container p { font-size: 16px; margin-top: 20px; color: #777; }
        .error-container a { color: #007bff; text-decoration: none; }
        .error-container a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="error-container">
        <h1>{$status}</h1>
        <h2>{$message}</h2>
        <p>The server encountered an error or the page you requested could not be found.</p>
        <p><a href="/">Go to Homepage</a></p>
    </div>
</body>
</html>
HTML;
        return new Response($html, $status, ['Content-Type' => 'text/html']);
    }
}