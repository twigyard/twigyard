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
     * @param string $basePath
     * @param string $cacheDir
     */
    public function __construct($basePath, $cacheDir)
    {
        $this->basePath = $basePath;
        $this->cacheDir = $cacheDir;
    }

    /**
     * @param $image
     * @return \Gregwar\Image\Image
     */
    public function getImage($image)
    {
        $image = new Image($this->assetDir . '/' . $image);
        $image->setCacheDir($this->basePath . '/' . $this->cacheDir);
        $image->setActualCacheDir($this->assetDir . '/' . $this->cacheDir);

        return $image;
    }

    /**
     * @param string $assetDir
     */
    public function setAssetDir($assetDir)
    {
        $this->assetDir = $assetDir;
    }
}
