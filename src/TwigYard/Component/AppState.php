<?php

namespace TwigYard\Component;

use TwigYard\Exception\MissingAppStateAttributeException;

class AppState
{
    /**
     * @var array
     */
    private $config;

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
     * @var string[]
     */
    private $trackingIds;

    /**
     * @var array
     */
    private $siteParameters;

    /**
     * @var string
     */
    private $remoteIp;

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * @param array $config
     * @return AppState
     */
    public function setConfig(array $config): AppState
    {
        $this->config = $config;

        return $this;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data ?: [];
    }

    /**
     * @param array $data
     * @return AppState
     */
    public function setData(array $data): AppState
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return string
     */
    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * @param string $locale
     * @return AppState
     */
    public function setLocale(string $locale): AppState
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * @return string
     */
    public function getPage(): string
    {
        return $this->page;
    }

    /**
     * @param string $page
     * @return AppState
     */
    public function setPage(string $page): AppState
    {
        $this->page = $page;

        return $this;
    }

    /**
     * @return array
     */
    public function getRouteMap(): array
    {
        return $this->routeMap;
    }

    /**
     * @param array $routeMap
     * @return AppState
     */
    public function setRouteMap(array $routeMap): AppState
    {
        $this->routeMap = $routeMap;

        return $this;
    }

    /**
     * @return string
     */
    public function getSiteDir(): string
    {
        return $this->siteDir;
    }

    /**
     * @param string $siteDir
     * @return AppState
     */
    public function setSiteDir(string $siteDir): AppState
    {
        $this->siteDir = $siteDir;

        return $this;
    }

    /**
     * @return array
     */
    public function getLocaleMap(): array
    {
        return $this->localeMap;
    }

    /**
     * @param array $localeMap
     * @return AppState
     */
    public function setLocaleMap(array $localeMap): AppState
    {
        $this->localeMap = $localeMap;

        return $this;
    }

    /**
     * @return array
     */
    public function getUrlParams(): array
    {
        return $this->urlParams ?: [];
    }

    /**
     * @param array $urlParams
     * @return AppState
     */
    public function setUrlParams(array $urlParams): AppState
    {
        $this->urlParams = $urlParams;

        return $this;
    }

    /**
     * @param string $name
     * @param string $urlParam
     * @return AppState
     */
    public function addUrlParam(string $name, string $urlParam): AppState
    {
        $this->urlParams[$name] = $urlParam;

        return $this;
    }

    /**
     * @return array
     */
    public function getForm(): array
    {
        return $this->form ?: [];
    }

    /**
     * @param array $form
     * @return AppState
     */
    public function setForm(array $form): AppState
    {
        $this->form = $form;

        return $this;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->config['url']['canonical'];
    }

    /**
     * @throws MissingAppStateAttributeException
     * @return bool
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

    /**
     * @return string
     */
    public function getScheme(): string
    {
        return $this->scheme;
    }

    /**
     * @param string $scheme
     * @return AppState
     */
    public function setScheme(string $scheme): AppState
    {
        $this->scheme = $scheme;

        return $this;
    }

    /**
     * @return array
     */
    public function getSiteParameters(): array
    {
        return $this->siteParameters ?: [];
    }

    /**
     * @param array $siteParameters
     * @return AppState
     */
    public function setSiteParameters(array $siteParameters): AppState
    {
        $this->siteParameters = $siteParameters;

        return $this;
    }

    /**
     * @return string
     */
    public function getRemoteIp(): string
    {
        return $this->remoteIp;
    }

    /**
     * @param string $remoteIp
     * @return AppState
     */
    public function setRemoteIp(string $remoteIp): AppState
    {
        $this->remoteIp = $remoteIp;

        return $this;
    }

    /**
     * @return string
     */
    public function getLanguageCode(): string
    {
        return $this->languageCode;
    }

    /**
     * @param string $languageCode
     * @return AppState
     */
    public function setLanguageCode(string $languageCode): AppState
    {
        $this->languageCode = $languageCode;

        return $this;
    }

    /**
     * @return array
     */
    public function dumpContext(): array
    {
        return [
            'config' => $this->config,
            'locale' => $this->locale,
            'page' => $this->page,
            'urlParams' => $this->urlParams,
            'sitesDir' => $this->siteDir,
        ];
    }
}
