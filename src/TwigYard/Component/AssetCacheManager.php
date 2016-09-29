<?php

namespace TwigYard\Component;

use Nette\Caching\Cache;

class AssetCacheManager
{
    /**
     * @var Cache
     */
    private $cache;

    /**
     * @var string
     */
    private $assetsDirOnFileSystem;

    /**
     * AssetCacheManager constructor.
     * @param \Nette\Caching\Cache $cache
     * @param string $assetsDirOnFileSystem
     */
    public function __construct(Cache $cache, $assetsDirOnFileSystem)
    {
        $this->cache = $cache;
        $this->assetsDirOnFileSystem = $assetsDirOnFileSystem;
    }

    /**
     * @param $file
     * @return int
     */
    public function getCrc32($file)
    {
        return $this->cache->load($file, function () use ($file) {
            if (!file_exists($this->assetsDirOnFileSystem . $file)) {
                return 0;
            }
            return crc32(file_get_contents($this->assetsDirOnFileSystem . $file));
        });
    }

    public function clean()
    {
        $this->cache->clean([Cache::ALL => true]);
    }
}
