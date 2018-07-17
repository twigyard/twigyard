<?php

namespace TwigYard\Component;

use Nette\Caching\Cache;
use Nette\Caching\Storages\DevNullStorage;
use Nette\Caching\Storages\FileStorage;

class ConfigCacheServiceFactory
{
    /**
     * @param LoggerFactory $loggerFactory
     * @param string $appRoot
     * @param string $cacheDir
     * @param string $cacheNamespace
     * @param bool $cacheEnabled
     * @param string $sitesDir
     * @param string $siteConfig
     * @return ConfigCache
     */
    public static function createConfigCache(
        LoggerFactory $loggerFactory,
        string $appRoot,
        string $cacheDir,
        string $cacheNamespace,
        bool $cacheEnabled,
        string $sitesDir,
        string $siteConfig
    ): ConfigCache {
        $cacheStorage = $cacheEnabled
            ? new FileStorage($appRoot . '/' . $cacheDir)
            : new DevNullStorage();
        $cache = new Cache($cacheStorage, $cacheNamespace);

        return new ConfigCache($cache, $loggerFactory, $appRoot . '/' . $sitesDir, $siteConfig);
    }
}
