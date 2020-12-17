<?php

namespace TwigYard\Component;

use Gregwar\Image\Image;

class ImageFactory
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
    private $basePath;

    /**
     * ImageFactory constructor.
     */
    public function __construct(string $basePath, string $cacheDir)
    {
        $this->basePath = $basePath;
        $this->cacheDir = $cacheDir;
    }

    public function getImage(string $image): Image
    {
        $image = new Image($this->assetDir . '/' . $image);
        $image->setCacheDir($this->basePath . '/' . $this->cacheDir);
        $image->setActualCacheDir($this->assetDir . '/' . $this->cacheDir);
        $image->enableProgressive();

        return $image;
    }

    public function setAssetDir(string $assetDir): void
    {
        $this->assetDir = $assetDir;
    }
}
