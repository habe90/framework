<?php

namespace TrueFrame\Http\Middleware;

use Closure;
use TrueFrame\Http\Request;
use TrueFrame\Http\Response;

interface MiddlewareInterface
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response;
}