<?php

namespace TwigYard\Middleware\Redirect;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TwigYard\Component\AppState;
use TwigYard\Middleware\MiddlewareInterface;
use Zend\Diactoros\Response;

class RedirectMiddleware implements MiddlewareInterface
{
    /**
     * @var \TwigYard\Component\AppState
     */
    private $appState;

    /**
     * @param \TwigYard\Component\AppState $appState
     */
    public function __construct(AppState $appState)
    {
        $this->appState = $appState;
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param callable|\TwigYard\Middleware\MiddlewareInterface $next
     * @return \Psr\Http\Message\ResponseInterface $response
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        if (array_key_exists('redirect', $this->appState->getConfig())) {
            $conf = $this->appState->getConfig()['redirect'];
            if (isset($conf[$request->getUri()->getPath()])) {
                return (new Response())
                    ->withHeader('Location', $conf[$request->getUri()->getPath()])
                    ->withStatus(301);
            }
        }

        return $next($request, $response);
    }
}
