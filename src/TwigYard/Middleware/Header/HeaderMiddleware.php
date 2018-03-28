<?php

namespace TwigYard\Middleware\Header;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TwigYard\Component\AppState;
use TwigYard\Middleware\MiddlewareInterface;

class HeaderMiddleware implements MiddlewareInterface
{
    const HEADER_CONFIG = 'header';

    const HEADER_CONTENT_SECURITY_POLICY = 'Content-Security-Policy';
    const HEADER_REFERRER_POLICY = 'Referrer-Policy';
    const HEADER_X_CONTENT_TYPE_OPTIONS = 'X-Content-Type-Options';

    const HEADER_DEFAULT_PARAMS = [
        self::HEADER_CONTENT_SECURITY_POLICY => 'default-src self;',
        self::HEADER_REFERRER_POLICY => 'strict-origin',
        self::HEADER_X_CONTENT_TYPE_OPTIONS => 'nosniff',
    ];

    const HEADER_DEFAULT_SECURE_PARAMS = [
        self::HEADER_CONTENT_SECURITY_POLICY => 'default-src https:;',
    ];

    /**
     * @var AppState
     */
    private $appState;

    /**
     * HeaderMiddleware constructor.
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
        $config = $this->appState->getConfig();
        $headers = self::HEADER_DEFAULT_PARAMS;

        if ($this->appState->getScheme() === 'https') {
            $headers = array_merge($headers, self::HEADER_DEFAULT_SECURE_PARAMS);
        }

        if (is_array($config) && array_key_exists(self::HEADER_CONFIG, $config)) {
            $headerConfig = $config[self::HEADER_CONFIG];

            if (is_array($headerConfig)) {
                if (array_key_exists(self::HEADER_CONTENT_SECURITY_POLICY, $headerConfig)) {
                    $headers[self::HEADER_CONTENT_SECURITY_POLICY] = '';

                    if (is_array($headerConfig[self::HEADER_CONTENT_SECURITY_POLICY])) {
                        foreach ($headerConfig[self::HEADER_CONTENT_SECURITY_POLICY] as $name => $value) {
                            if (is_array($value)) {
                                $headers[self::HEADER_CONTENT_SECURITY_POLICY] .= sprintf(
                                    '%s %s; ',
                                    $name,
                                    implode(' ', $value)
                                );
                            }
                        }

                        $headers[self::HEADER_CONTENT_SECURITY_POLICY] = rtrim($headers[self::HEADER_CONTENT_SECURITY_POLICY]);
                    }

                    if (!strlen($headers[self::HEADER_CONTENT_SECURITY_POLICY])) {
                        unset($headers[self::HEADER_CONTENT_SECURITY_POLICY]);
                    }
                }

                if (array_key_exists(self::HEADER_REFERRER_POLICY, $headerConfig)) {
                    $headers[self::HEADER_REFERRER_POLICY] = $headerConfig[self::HEADER_REFERRER_POLICY];
                }

                if (array_key_exists(self::HEADER_X_CONTENT_TYPE_OPTIONS, $headerConfig)) {
                    $headers[self::HEADER_X_CONTENT_TYPE_OPTIONS] = $headerConfig[self::HEADER_X_CONTENT_TYPE_OPTIONS];
                }
            } else {
                foreach ($headers as $name => $value) {
                    unset($headers[$name]);
                }
            }
        }

        foreach ($headers as $name => $value) {
            if ($value) {
                $response = $response->withHeader($name, $value);
            }
        }

        return $next($request, $response);
    }
}
