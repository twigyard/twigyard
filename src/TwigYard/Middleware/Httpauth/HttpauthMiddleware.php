<?php

namespace TwigYard\Middleware\Httpauth;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TwigYard\Component\AppState;
use TwigYard\Middleware\MiddlewareInterface;
use Zend\Diactoros\Response;

class HttpauthMiddleware implements MiddlewareInterface
{
    /**
     * @var AppState
     */
    private $appState;

    /**
     * @param AppState $appState
     */
    public function __construct(AppState $appState)
    {
        $this->appState = $appState;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable $next
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        if (array_key_exists('httpauth', $this->appState->getMiddlewareConfig())) {
            $conf = $this->appState->getMiddlewareConfig()['httpauth'];
            $username = '';
            $password = '';
            $authParams = isset($request->getQueryParams()['httpauth']) ? $request->getQueryParams()['httpauth'] : null;
            $clientIp = $request->getServerParams()['REMOTE_ADDR'];

            if (array_key_exists('exclude_ip_addresses', $conf) && in_array($clientIp, $conf['exclude_ip_addresses'])) {
                return $next($request, $response);
            }

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
