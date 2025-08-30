<?php

namespace TrueFrame\Http;

class Response
{
    /**
     * The response content.
     *
     * @var string
     */
    protected string $content;

    /**
     * The response status code.
     *
     * @var int
     */
    protected int $statusCode;

    /**
     * The response headers.
     *
     * @var array
     */
    protected array $headers;

    /**
     * Create a new Response instance.
     *
     * @param string $content
     * @param int $statusCode
     * @param array $headers
     */
    public function __construct(string $content = '', int $statusCode = 200, array $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    /**
     * Set the response content.
     *
     * @param string $content
     * @return $this
     */
    public function setContent(string $content): static
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Get the response content.
     *
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Set the response status code.
     *
     * @param int $statusCode
     * @return $this
     */
    public function setStatusCode(int $statusCode): static
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    /**
     * Get the response status code.
     *
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Set a response header.
     *
     * @param string $name
     * @param string $value
     * @return $this
     */
    public function header(string $name, string $value): static
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Get a response header.
     *
     * @param string $name
     * @param mixed $default
     * @return string|null
     */
    public function getHeader(string $name, mixed $default = null): ?string
    {
        return $this->headers[$name] ?? $default;
    }

    /**
     * Set the response to JSON.
     *
     * @param mixed $data
     * @param int $statusCode
     * @param array $headers
     * @return $this
     */
    public function json(mixed $data, int $statusCode = 200, array $headers = []): static
    {
        $this->setContent(json_encode($data));
        $this->setStatusCode($statusCode);
        $this->header('Content-Type', 'application/json');
        foreach ($headers as $name => $value) {
            $this->header($name, $value);
        }
        return $this;
    }

    /**
     * Send the response to the browser.
     *
     * @return void
     */
    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->statusCode);
            foreach ($this->headers as $name => $value) {
                header("{$name}: {$value}");
            }
        }
        echo $this->content;
    }
}