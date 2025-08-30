<?php

namespace TrueFrame\Http;

use Closure;
use TrueFrame\Application;
use TrueFrame\Http\Middleware\MiddlewareInterface;

/**
 * A simple HTTP pipeline for processing requests through middleware.
 */
class Pipeline
{
    /**
     * The application container.
     *
     * @var Application
     */
    protected Application $app;

    /**
     * The object being passed through the pipeline.
     *
     * @var mixed
     */
    protected mixed $passable;

    /**
     * The array of pipes.
     *
     * @var array
     */
    protected array $pipes = [];

    /**
     * The method to call on each pipe.
     *
     * @var string
     */
    protected string $method = 'handle';

    /**
     * Create a new pipeline instance.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Set the object being sent through the pipeline.
     *
     * @param mixed $passable
     * @return $this
     */
    public function send(mixed $passable): static
    {
        $this->passable = $passable;
        return $this;
    }

    /**
     * Set the array of pipes.
     *
     * @param array $pipes
     * @return $this
     */
    public function through(array $pipes): static
    {
        $this->pipes = $pipes;
        return $this;
    }

    /**
     * Run the pipeline with a final destination callback.
     *
     * @param Closure $destination
     * @return mixed
     */
    public function then(Closure $destination): mixed
    {
        $pipeline = array_reduce(
            array_reverse($this->pipes),
            $this->carry(),
            $this->prepareDestination($destination)
        );

        return $pipeline($this->passable);
    }

    /**
     * Get the closure that represents a "carry" handler for the pipeline.
     *
     * @return Closure
     */
    protected function carry(): Closure
    {
        return function ($stack, $pipe) {
            return function ($passable) use ($stack, $pipe) {
                if ($pipe instanceof Closure) {
                    return $pipe($passable, $stack);
                } elseif (! $pipe instanceof MiddlewareInterface) {
                    $pipe = $this->app->make($pipe);
                }

                return $pipe->{$this->method}($passable, $stack);
            };
        };
    }

    /**
     * Prepare the final destination callback for the pipeline.
     *
     * @param Closure $destination
     * @return Closure
     */
    protected function prepareDestination(Closure $destination): Closure
    {
        return function ($passable) use ($destination) {
            return $destination($passable);
        };
    }
}