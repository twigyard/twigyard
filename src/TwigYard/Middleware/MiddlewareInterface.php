<?php

namespace TwigYard\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

interface MiddlewareInterface
{
    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param callable $next
     * @return \Psr\Http\Message\ResponseInterface $response
     */
    public function __invoke(Request $request, Response $response, callable $next);
}
