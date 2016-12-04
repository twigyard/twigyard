<?php

namespace TwigYard\Component;

use Nette\Caching\Cache;
use Nette\Caching\Storages\DevNullStorage;
use Nette\Caching\Storages\FileStorage;
use Nette\DirectoryNotFoundException;

class AssetCacheManagerFactory
{
    /**
     * @var string
     */
    private $cacheDir;

    /**
     * @var string
     */
    private $assetDir;

    /**
     * @var string
     */
    private $cacheNamespace;

    /**
     * AssetManagerFactory constructor.
     * @param $cacheNamespace
     */
    public function __construct($cacheNamespace)
    {
        $this->cacheNamespace = $cacheNamespace;
    }

    /**
     * @return AssetCacheManager
     */
    public function createAssetCacheManager()
    {
        if ($this->cacheDir) {
            try {
                $cacheStorage = new FileStorage($this->cacheDir);
            } catch (DirectoryNotFoundException $e) {
                if (!@mkdir($this->cacheDir, 0755, true)) {
                    throw $e;
                }
                $cacheStorage = new FileStorage($this->cacheDir);
            }
        } else {
            $cacheStorage = new DevNullStorage();
        }

        $cache = new Cache($cacheStorage, $this->cacheNamespace);

        return new AssetCacheManager($cache, $this->assetDir);
    }

    /**
     * @param string $assetDir
     */
    public function setAssetDir($assetDir)
    {
        $this->assetDir = $assetDir;
    }

    /**
     * @param string $cacheDir
     */
    public function setCacheDir($cacheDir)
    {
        $this->cacheDir = $cacheDir;
    }
}
