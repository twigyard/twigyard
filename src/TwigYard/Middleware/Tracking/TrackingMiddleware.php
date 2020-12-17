<?php

namespace TwigYard\Middleware\Tracking;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TwigYard\Component\AppState;
use TwigYard\Middleware\MiddlewareInterface;

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
     * @param bool $enableTracking
     */
    public function __construct(AppState $appState, $enableTracking)
    {
        $this->appState = $appState;
        $this->enableTracking = $enableTracking;
    }

    /**
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        if ($this->enableTracking && array_key_exists('tracking', $this->appState->getMiddlewareConfig())) {
            $trackingIds = $this->appState->getMiddlewareConfig()['tracking'];
            $this->appState->setTrackingIds($trackingIds);
        }

        return $next($request, $response);
    }
}
