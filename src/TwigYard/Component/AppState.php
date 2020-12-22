<?php

namespace TwigYard\Component;

use TwigYard\Exception\MissingAppStateAttributeException;

class AppState
{
    /**
     * @var array
     */
    private $middlewareConfig;

    /**
     * @var array
     */
    private $componentConfig;

    /**
     * @var array
     */
    private $data;

    /**
     * @var array
     */
    private $form;

    /**
     * @var string
     */
    private $languageCode;

    /**
     * @var string
     */
    private $locale;

    /**
     * @var array
     */
    private $localeMap;

    /**
     * @var string
     */
    private $page;

    /**
     * @var array
     */
    private $urlParams;

    /**
     * @var array
     */
    private $routeMap;

    /**
     * @var string
     */
    private $scheme;

    /**
     * @var string
     */
    private $siteDir;

    /**
     * @var string
     */
    private $host;

    /**
     * @var string[]
     */
    private $trackingIds;

    /**
     * @var string
     */
    private $remoteIp;

    /**
     * @var string
     */
    private $sitesDir;

    /**
     * AppState constructor.
     */
    public function __construct(string $sitesDir)
    {
        $this->sitesDir = $sitesDir;
    }

    public function getMiddlewareConfig(): array
    {
        return $this->middlewareConfig;
    }

    public function setMiddlewareConfig(array $config): AppState
    {
        $this->middlewareConfig = $config;

        return $this;
    }

    public function getData(): array
    {
        return $this->data ?: [];
    }

    public function setData(array $data): AppState
    {
        $this->data = $data;

        return $this;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): AppState
    {
        $this->locale = $locale;

        return $this;
    }

    public function getPage(): string
    {
        return $this->page;
    }

    public function setPage(string $page): AppState
    {
        $this->page = $page;

        return $this;
    }

    public function getRouteMap(): array
    {
        return $this->routeMap;
    }

    public function setRouteMap(array $routeMap): AppState
    {
        $this->routeMap = $routeMap;

        return $this;
    }

    public function getSiteDir(): string
    {
        return $this->sitesDir . '/' . $this->getHost();
    }

    public function getLocaleMap(): array
    {
        return $this->localeMap;
    }

    public function setLocaleMap(array $localeMap): AppState
    {
        $this->localeMap = $localeMap;

        return $this;
    }

    public function getUrlParams(): array
    {
        return $this->urlParams ?: [];
    }

    public function setUrlParams(array $urlParams): AppState
    {
        $this->urlParams = $urlParams;

        return $this;
    }

    public function addUrlParam(string $name, string $urlParam): AppState
    {
        $this->urlParams[$name] = $urlParam;

        return $this;
    }

    public function getForm(): array
    {
        return $this->form ?: [];
    }

    public function setForm(array $form): AppState
    {
        $this->form = $form;

        return $this;
    }

    public function getUrl(): ?string
    {
        if ($this->middlewareConfig) {
            return $this->middlewareConfig['url']['canonical'];
        }

        return null;
    }

    /**
     * @throws MissingAppStateAttributeException
     */
    public function isSingleLanguage(): bool
    {
        if (!$this->getLocale()) {
            throw new MissingAppStateAttributeException('Locale is not yet defined.');
        }

        return $this->getLocaleMap()[$this->getLocale()] === '';
    }

    /**
     * @return string[]
     */
    public function getTrackingIds(): array
    {
        return $this->trackingIds ?: [];
    }

    /**
     * @param string[] $trackingIds
     * @return $this
     */
    public function setTrackingIds(array $trackingIds): AppState
    {
        $this->trackingIds = $trackingIds;

        return $this;
    }

    public function getScheme(): string
    {
        return $this->scheme;
    }

    public function setScheme(string $scheme): AppState
    {
        $this->scheme = $scheme;

        return $this;
    }

    public function getRemoteIp(): string
    {
        return $this->remoteIp;
    }

    public function setRemoteIp(string $remoteIp): AppState
    {
        $this->remoteIp = $remoteIp;

        return $this;
    }

    public function getLanguageCode(): string
    {
        return $this->languageCode;
    }

    public function setLanguageCode(string $languageCode): AppState
    {
        $this->languageCode = $languageCode;

        return $this;
    }

    public function dumpContext(): array
    {
        return [
            'middlewareConfig' => $this->middlewareConfig,
            'componentConfig' => $this->componentConfig,
            'locale' => $this->locale,
            'page' => $this->page,
            'urlParams' => $this->urlParams,
            'sitesDir' => $this->siteDir,
        ];
    }

    public function getComponentConfig(): array
    {
        return $this->componentConfig;
    }

    public function setComponentConfig(array $componentConfig): AppState
    {
        $this->componentConfig = $componentConfig;

        return $this;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function setHost(string $host): AppState
    {
        $this->host = $host;

        return $this;
    }
}
