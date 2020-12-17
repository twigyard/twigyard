<?php

namespace TwigYard\Component;

use Nette\Caching\Cache;
use Nette\Caching\Storages\DevNullStorage;
use Nette\Caching\Storages\FileStorage;
use Nette\DirectoryNotFoundException;

class AssetCacheManagerFactory
{
    /**
     * @var string|null
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
     */
    public function __construct(string $cacheNamespace)
    {
        $this->cacheNamespace = $cacheNamespace;
    }

    public function createAssetCacheManager(): AssetCacheManager
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

    public function setAssetDir(string $assetDir): void
    {
        $this->assetDir = $assetDir;
    }

    public function setCacheDir(?string $cacheDir): void
    {
        $this->cacheDir = $cacheDir;
    }
}
