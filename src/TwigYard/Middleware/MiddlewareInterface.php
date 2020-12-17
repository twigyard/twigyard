<?php

namespace TwigYard\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

interface MiddlewareInterface
{
    /**
     * @return ResponseInterface
     */
    public function __invoke(Request $request, Response $response, callable $next);
}
