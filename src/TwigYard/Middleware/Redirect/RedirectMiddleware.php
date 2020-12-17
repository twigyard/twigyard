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
     * @var AppState
     */
    private $appState;

    public function __construct(AppState $appState)
    {
        $this->appState = $appState;
    }

    /**
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        if (array_key_exists('redirect', $this->appState->getMiddlewareConfig())) {
            $conf = $this->appState->getMiddlewareConfig()['redirect'];
            if (isset($conf[$request->getUri()->getPath()])) {
                return (new Response())
                    ->withHeader('Location', $conf[$request->getUri()->getPath()])
                    ->withStatus(301);
            }
        }

        return $next($request, $response);
    }
}
