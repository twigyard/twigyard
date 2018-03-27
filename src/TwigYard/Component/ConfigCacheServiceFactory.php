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
     * @return ConfigCache
     */
    public static function createConfigCache(
        LoggerFactory $loggerFactory,
        string $appRoot,
        string $cacheDir,
        string $cacheNamespace,
        bool $cacheEnabled
    ): ConfigCache {
        $cacheStorage = $cacheEnabled
            ? new FileStorage($appRoot . '/' . $cacheDir)
            : new DevNullStorage();
        $cache = new Cache($cacheStorage, $cacheNamespace);

        return new ConfigCache($cache, $loggerFactory);
    }
}
