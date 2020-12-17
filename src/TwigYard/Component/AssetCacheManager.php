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
     */
    public function __construct(Cache $cache, string $assetsDirOnFileSystem)
    {
        $this->cache = $cache;
        $this->assetsDirOnFileSystem = $assetsDirOnFileSystem;
    }

    public function getCrc32(string $file): int
    {
        return $this->cache->load($file, function () use ($file) {
            if (!file_exists($this->assetsDirOnFileSystem . $file)) {
                return 0;
            }
            $fileContent = file_get_contents($this->assetsDirOnFileSystem . $file);

            return $fileContent ? crc32($fileContent) : 0;
        });
    }

    public function clean(): void
    {
        $this->cache->clean([Cache::ALL => true]);
    }
}
