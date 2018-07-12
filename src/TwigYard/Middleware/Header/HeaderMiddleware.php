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
        self::HEADER_CONTENT_SECURITY_POLICY => 'default-src * \'unsafe-inline\' \'unsafe-eval\';',
        self::HEADER_REFERRER_POLICY => 'strict-origin',
        self::HEADER_X_CONTENT_TYPE_OPTIONS => 'nosniff',
    ];

    const HEADER_DEFAULT_SECURE_PARAMS = [
        self::HEADER_CONTENT_SECURITY_POLICY => 'default-src https: \'unsafe-inline\' \'unsafe-eval\';',
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
            if (is_array($config[self::HEADER_CONFIG])) {
                $headers = $this->setContentSecurityPolicyHeader($headers, $config[self::HEADER_CONFIG]);
                $headers = $this->setRefererPolicyHeader($headers, $config[self::HEADER_CONFIG]);
                $headers = $this->setXContentTypeOptionsHeader($headers, $config[self::HEADER_CONFIG]);
            } else {
                $headers = $this->resetHeaders($headers);
            }
        }

        foreach ($headers as $name => $value) {
            if ($value) {
                $response = $response->withHeader($name, $value);
            }
        }

        return $next($request, $response);
    }

    /**
     * @param array $headers
     * @param array $headerConfig
     * @return array
     */
    private function setContentSecurityPolicyHeader(array $headers, array $headerConfig): array
    {
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

            if (!mb_strlen($headers[self::HEADER_CONTENT_SECURITY_POLICY])) {
                unset($headers[self::HEADER_CONTENT_SECURITY_POLICY]);
            }
        }

        if (array_key_exists(self::HEADER_REFERRER_POLICY, $headerConfig)) {
            $headers[self::HEADER_REFERRER_POLICY] = $headerConfig[self::HEADER_REFERRER_POLICY];
        }

        return $headers;
    }

    /**
     * @param array $headers
     * @param array $headerConfig
     * @return array
     */
    private function setRefererPolicyHeader(array $headers, array $headerConfig): array
    {
        if (array_key_exists(self::HEADER_REFERRER_POLICY, $headerConfig)) {
            $headers[self::HEADER_REFERRER_POLICY] = $headerConfig[self::HEADER_REFERRER_POLICY];
        }

        return $headers;
    }

    /**
     * @param array $headers
     * @param array $headerConfig
     * @return array
     */
    private function setXContentTypeOptionsHeader(array $headers, array $headerConfig): array
    {
        if (array_key_exists(self::HEADER_X_CONTENT_TYPE_OPTIONS, $headerConfig)) {
            $headers[self::HEADER_X_CONTENT_TYPE_OPTIONS] = $headerConfig[self::HEADER_X_CONTENT_TYPE_OPTIONS];
        }

        return $headers;
    }

    /**
     * @param array $headers
     * @return array
     */
    private function resetHeaders(array $headers): array
    {
        foreach ($headers as $name => $value) {
            unset($headers[$name]);
        }

        return $headers;
    }
}
