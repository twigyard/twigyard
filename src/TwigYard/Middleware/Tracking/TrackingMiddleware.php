<?php

namespace TwigYard\Middleware\Tracking;

use TwigYard\Component\AppState;

use TwigYard\Middleware\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class TrackingMiddleware implements MiddlewareInterface
{

    /**
     * @var AppState
     */
    private $appState;

    /**
     * @var bool
     */
    private $enableTracking;

    /**
     * @param AppState $appState
     * @param bool $enableTracking
     */
    public function __construct(AppState $appState, $enableTracking)
    {
        $this->appState = $appState;
        $this->enableTracking = $enableTracking;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable|MiddlewareInterface $next
     * @return ResponseInterface $response
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        if ($this->enableTracking && array_key_exists('tracking', $this->appState->getConfig())) {
            $trackingIds = $this->appState->getConfig()['tracking'];
            $this->appState->setTrackingIds($trackingIds);
        }

        return $next($request, $response);
    }
}
