<?php

namespace TwigYard\Middleware\Httpauth;

use TwigYard\Component\AppState;
use TwigYard\Middleware\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;

class HttpauthMiddleware implements MiddlewareInterface
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
     * @throws \TwigYard\Exception\InvalidSiteConfigException
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        if (array_key_exists('httpauth', $this->appState->getConfig())) {
            $conf = $this->appState->getConfig()['httpauth'];
            $username = '';
            $password = '';
            $authParams = isset($request->getQueryParams()['httpauth']) ? $request->getQueryParams()['httpauth'] : null;

            if ($authParams) {
                $httpAuthString = base64_decode(substr($authParams, 6));
                list($username, $password) = explode(':', $httpAuthString);
            }
            if ($username !== $conf['username'] || $password !== $conf['password']) {
                 return (new Response())
                    ->withHeader('WWW-Authenticate', sprintf('Basic realm=""'))
                    ->withStatus(401);
            }
        }

        return $next($request, $response);
    }
}
