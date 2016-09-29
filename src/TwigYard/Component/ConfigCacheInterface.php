<?php

namespace TwigYard\Component;

interface ConfigCacheInterface
{
    /**
     * @param string $sitesDir
     * @param string $siteConfig
     */
    public function getConfig($sitesDir, $siteConfig);
}
