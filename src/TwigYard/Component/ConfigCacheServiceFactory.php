<?php

namespace TwigYard\Component;

use Nette\Caching\Cache;
use Nette\Caching\Storages\DevNullStorage;
use Nette\Caching\Storages\FileStorage;

class ConfigCacheServiceFactory
{
    /**
     * @param LoggerFactory $loggerFactory
     * @param $appRoot
     * @param $cacheDir
     * @param $cacheNamespace
     * @param $cacheEnabled
     * @return ConfigCache
     */
    public static function createConfigCache(
        LoggerFactory $loggerFactory,
        $appRoot,
        $cacheDir,
        $cacheNamespace,
        $cacheEnabled
    ) {
        $cacheStorage = $cacheEnabled
            ? new FileStorage($appRoot . '/' . $cacheDir)
            : new DevNullStorage();
        $cache = new Cache($cacheStorage, $cacheNamespace);

        return new ConfigCache($cache, $loggerFactory);
    }
}
