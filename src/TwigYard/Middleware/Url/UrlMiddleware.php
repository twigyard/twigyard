<?php

namespace TwigYard\Middleware\Url;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TwigYard\Component\AppState;
use TwigYard\Middleware\MiddlewareInterface;
use Zend\Diactoros\Response\RedirectResponse;

class UrlMiddleware implements MiddlewareInterface
{
    /**
     * @var AppState
     */
    private $appState;

    /**
     * @var string|null
     */
    private $parentDomain;

    /**
     * @var bool
     */
    private $sslAllowed;

    /**
     * UrlMiddleware constructor.
     * @param AppState $appState
     * @param bool $sslAllowed
     * @param string|null $parentDomain
     */
    public function __construct(
        AppState $appState,
        bool $sslAllowed,
        ?string $parentDomain
    ) {
        $this->appState = $appState;
        $this->sslAllowed = $sslAllowed;
        $this->parentDomain = $parentDomain;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable $next
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $canonicalUrl = $this->appState->getMiddlewareConfig()['url']['canonical'];
        $host = $this->appState->getHost();

        if ($host !== $canonicalUrl) {
            return new RedirectResponse(
                $request->getUri()->getScheme() . '://' . $canonicalUrl
                    . ($this->parentDomain ? sprintf('.%s', $this->parentDomain) : '')
                    . $request->getUri()->getPath(),
                301
            );
        }

        if ($this->sslAllowed
            && !empty($this->appState->getMiddlewareConfig()['url']['ssl'])
            && $request->getUri()->getScheme() !== 'https'
        ) {
            return new RedirectResponse(
                'https://' . $host
                    . ($this->parentDomain ? sprintf('.%s', $this->parentDomain) : '')
                    . $request->getUri()->getPath(),
                301
            );
        }

        return $next($request, $response);
    }
}
