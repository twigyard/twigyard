<?php

namespace TwigYard\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

interface MiddlewareInterface
{
    /**
     * @param Request $request
     * @param ResponseInterface $response
     * @param callable $next
     * @return ResponseInterface
     */
    public function __invoke(Request $request, Response $response, callable $next);
}
