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
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param array $config
     * @return AppState
     */
    public function setConfig(array $config)
    {
        $this->config = $config;
        return $this;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param array $data
     * @return AppState
     */
    public function setData(array $data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @param string $locale
     * @return AppState
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
        return $this;
    }

    /**
     * @return string
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * @param string $page
     * @return AppState
     */
    public function setPage($page)
    {
        $this->page = $page;
        return $this;
    }

    /**
     * @return array
     */
    public function getRouteMap()
    {
        return $this->routeMap;
    }

    /**
     * @param array $routeMap
     * @return AppState
     */
    public function setRouteMap(array $routeMap)
    {
        $this->routeMap = $routeMap;
        return $this;
    }

    /**
     * @return string
     */
    public function getSiteDir()
    {
        return $this->siteDir;
    }

    /**
     * @param string $siteDir
     * @return AppState
     */
    public function setSiteDir($siteDir)
    {
        $this->siteDir = $siteDir;
        return $this;
    }

    /**
     * @return array
     */
    public function getLocaleMap()
    {
        return $this->localeMap;
    }

    /**
     * @param array $localeMap
     * @return $this
     */
    public function setLocaleMap(array $localeMap)
    {
        $this->localeMap = $localeMap;
        return $this;
    }

    /**
     * @return array
     */
    public function getUrlParams()
    {
        return $this->urlParams;
    }

    /**
     * @param array $urlParams
     * @return AppState
     */
    public function setUrlParams(array $urlParams)
    {
        $this->urlParams = $urlParams;
        return $this;
    }

    /**
     * @param string $name
     * @param string $urlParam
     * @return \TwigYard\Component\AppState
     */
    public function addUrlParam($name, $urlParam)
    {
        $this->urlParams[$name] = $urlParam;
        return $this;
    }

    /**
     * @return array
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * @param array $form
     * @return AppState
     */
    public function setForm(array $form)
    {
        $this->form = $form;
        return $this;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->config['url']['canonical'];
    }

    /**
     * @return bool
     * @throws MissingAppStateAttributeException
     */
    public function isSingleLanguage()
    {
        if (!$this->getLocale()) {
            throw new MissingAppStateAttributeException('Locale is not yet defined.');
        }

        return $this->getLocaleMap()[$this->getLocale()] === '';
    }

    /**
     * @return string[]
     */
    public function getTrackingIds()
    {
        return $this->trackingIds;
    }

    /**
     * @param string[] $trackingIds
     * @return $this
     */
    public function setTrackingIds(array $trackingIds)
    {
        $this->trackingIds = $trackingIds;
        return $this;
    }

    /**
     * @return array
     */
    public function getSiteParameters()
    {
        return $this->siteParameters;
    }

    /**
     * @param array $siteParameters
     * @return AppState
     */
    public function setSiteParameters(array $siteParameters)
    {
        $this->siteParameters = $siteParameters;
        return $this;
    }

    /**
     * @return string
     */
    public function getRemoteIp()
    {
        return $this->remoteIp;
    }

    /**
     * @param string $remoteIp
     * @return AppState
     */
    public function setRemoteIp($remoteIp)
    {
        $this->remoteIp = $remoteIp;
        return $this;
    }

    /**
     * @return string
     */
    public function getLanguageCode()
    {
        return $this->languageCode;
    }

    /**
     * @param string $languageCode
     * @return AppState
     */
    public function setLanguageCode($languageCode)
    {
        $this->languageCode = $languageCode;
        return $this;
    }
    
    /**
     * @return array
     */
    public function dumpContext()
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
