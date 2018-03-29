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
     * @param Cache $cache
     * @param string $assetsDirOnFileSystem
     */
    public function __construct(Cache $cache, string $assetsDirOnFileSystem)
    {
        $this->cache = $cache;
        $this->assetsDirOnFileSystem = $assetsDirOnFileSystem;
    }

    /**
     * @param string $file
     * @return int
     */
    public function getCrc32(string $file): int
    {
        return $this->cache->load($file, function () use ($file) {
            if (!file_exists($this->assetsDirOnFileSystem . $file)) {
                return 0;
            }

            return crc32(file_get_contents($this->assetsDirOnFileSystem . $file));
        });
    }

    public function clean(): void
    {
        $this->cache->clean([Cache::ALL => true]);
    }
}
