<?php

namespace TwigYard\Component;

interface ConfigCacheInterface
{
    /**
     * @param string $sitesDir
     * @param string $siteConfig
     * @return array
     */
    public function getConfig(string $sitesDir, string $siteConfig): array;
}
