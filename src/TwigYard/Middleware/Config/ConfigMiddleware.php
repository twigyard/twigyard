<?php

namespace TwigYard\Middleware\Config;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TwigYard\Component\AppState;
use TwigYard\Component\ConfigCacheInterface;
use TwigYard\Exception\InvalidSiteConfigException;
use TwigYard\Middleware\MiddlewareInterface;

class ConfigMiddleware implements MiddlewareInterface
{
    /**
     * @var AppState
     */
    private $appState;

    /**
     * @var ConfigCacheInterface
     */
    private $configCache;

    /**
     * @var string
     */
    private $sitesDir;

    /**
     * @var string
     */
    private $siteConfig;

    /**
     * @var string|null
     */
    private $parentDomain;

    /**
     * UrlMiddleware constructor.
     * @param AppState $appState
     * @param ConfigCacheInterface $configCache
     * @param string $sitesDir
     * @param string $siteConfig
     * @param string $parentDomain
     */
    public function __construct(
        AppState $appState,
        ConfigCacheInterface $configCache,
        string $sitesDir,
        string $siteConfig,
        ?string $parentDomain
    ) {
        $this->appState = $appState;
        $this->configCache = $configCache;
        $this->sitesDir = $sitesDir;
        $this->siteConfig = $siteConfig;
        $this->parentDomain = $parentDomain;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable $next
     * @throws InvalidSiteConfigException
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $configs = $this->configCache->getConfig();
        $host = $request->getUri()->getHost();

        if ($this->parentDomain) {
            $host = substr($host, 0, -strlen($this->parentDomain) - 1);
        }

        if (!isset($configs[$host])) {
            if (file_exists($this->sitesDir . '/' . $host . '/' . $this->siteConfig)) {
                throw new InvalidSiteConfigException(sprintf('Config for %s site is invalid.', $host));
            } else {
                return $response->withStatus(404);
            }
        }

        $middlewareConfig = [];
        $componentConfig = [];
        $configVersion = $configs[$host]['version'] ?? 1;

        if ($configVersion === 1) {
            $middlewareConfig = $configs[$host];
        } elseif ($configVersion === 2) {
            $middlewareConfig = $configs[$host]['middlewares'];
            $componentConfig = $configs[$host]['components'];
        } else {
            throw new InvalidSiteConfigException('Invalid config version.');
        }

        $this->appState
            ->setMiddlewareConfig($middlewareConfig)
            ->setComponentConfig($componentConfig)
            ->setHost($host)
            ->setScheme(
                isset($request->getServerParams()['REQUEST_SCHEME'])
                    ? $request->getServerParams()['REQUEST_SCHEME']
                    : 'http'
            )
            ->setRemoteIp($request->getServerParams()['REMOTE_ADDR']);

        return $next($request, $response);
    }
}
