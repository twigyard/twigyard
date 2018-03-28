<?php

namespace TwigYard\Middleware\Url;

use TwigYard\Component\AppState;
use TwigYard\Component\ConfigCacheInterface;
use TwigYard\Exception\InvalidSiteConfigException;
use TwigYard\Middleware\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Yaml\Yaml;
use Zend\Diactoros\Response\RedirectResponse;

class UrlMiddleware implements MiddlewareInterface
{
    /**
     * @var \TwigYard\Component\AppState
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
     * @var string
     */
    private $siteParameters;

    /**
     * @var string
     */
    private $parentDomain;
    
    /**
     * @var bool
     */
    private $sslAllowed;

    /**
     * @param \TwigYard\Component\AppState $appState
     * @param \TwigYard\Component\ConfigCacheInterface $configCache
     * @param string $sitesDir
     * @param string $siteConfig
     * @param string $siteParameters
     * @param string $parentDomain
     * @param boolean $sslAllowed
     */
    public function __construct(
        AppState $appState,
        ConfigCacheInterface $configCache,
        $sitesDir,
        $siteConfig,
        $siteParameters,
        $parentDomain,
        $sslAllowed
    ) {
        $this->appState = $appState;
        $this->configCache = $configCache;
        $this->sitesDir = $sitesDir;
        $this->siteConfig = $siteConfig;
        $this->siteParameters = $siteParameters;
        $this->parentDomain = $parentDomain;
        $this->sslAllowed = $sslAllowed;
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
        $configs = $this->configCache->getConfig($this->sitesDir, $this->siteConfig);
        $host = $request->getUri()->getHost();

        if ($this->parentDomain) {
            $host = substr($host, 0, - strlen($this->parentDomain) - 1);
        }

        if (!isset($configs[$host])) {
            if (file_exists($this->sitesDir . '/' . $host . '/' . $this->siteConfig)) {
                throw new InvalidSiteConfigException(sprintf('Config for %s site is invalid.', $host));
            } else {
                return $response->withStatus(404);
            }
        }

        $canonicalUrl = $configs[$host]['url']['canonical'];
        if ($host !== $canonicalUrl) {
            return new RedirectResponse($request->getUri()->getScheme() . '://' . $canonicalUrl, 301);
        }

        if ($this->sslAllowed
            && !empty($configs[$host]['url']['ssl'])
            && $request->getUri()->getScheme() !== 'https'
        ) {
            return new RedirectResponse('https://' . $host, 301);
        }

        $this->appState
            ->setConfig($configs[$host])
            ->setSiteDir($this->sitesDir . '/' . $host)
            ->setScheme(
                isset($request->getServerParams()['REQUEST_SCHEME'])
                    ? $request->getServerParams()['REQUEST_SCHEME']
                    : 'http'
            )
            ->setRemoteIp($request->getServerParams()['REMOTE_ADDR']);
        if (file_exists($this->appState->getSiteDir() . '/' . $this->siteParameters)) {
            $this->appState->setSiteParameters(
                Yaml::parse(
                    file_get_contents($this->appState->getSiteDir() . '/' . $this->siteParameters)
                )['parameters']
            );
        } else {
            $this->appState->setSiteParameters([]);
        }

        return $next($request, $response);
    }
}
