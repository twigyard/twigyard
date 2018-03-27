<?php

namespace TwigYard\Component;

use Symfony\Component\VarDumper\VarDumper;
use TwigYard\Exception\InvalidRouteException;

class TemplatingClosureFactory
{
    /**
     * @var string
     */
    private $basePath;

    /**
     * @var \TwigYard\Component\ImageFactory
     */
    private $imageFactory;

    /**
     * @var \TwigYard\Component\AssetCacheManagerFactory
     */
    private $assetCacheManagerFactory;

    /**
     * TemplatingClosure constructor.
     * @param string $basePath
     * @param \TwigYard\Component\ImageFactory $imageFactory
     * @param \TwigYard\Component\AssetCacheManagerFactory $cacheManagerFactory
     */
    public function __construct($basePath, ImageFactory $imageFactory, AssetCacheManagerFactory $cacheManagerFactory)
    {
        $this->basePath = $basePath;
        $this->imageFactory = $imageFactory;
        $this->assetCacheManagerFactory = $cacheManagerFactory;
    }

    /**
     * @param array $routeMap
     * @param string $locale
     * @return \Closure
     */
    public function getPathClosure(array $routeMap, $locale)
    {
        return function ($pageName, array $query = [], $localeForce = null) use ($routeMap, $locale) {
            $locale = $localeForce ? $localeForce : $locale;
            if (!array_key_exists($pageName, $routeMap)) {
                throw new InvalidRouteException('The requested page name is not defined');
            }
            if (!array_key_exists($locale, $routeMap[$pageName])) {
                return $routeMap['index'][$locale];
            }

            $values = [];
            $patterns = [];
            foreach ($query as $key => $value) {
                $patterns[] = '/\{' . $key . '\}/';
                $values[] = $value;
            }

            $path = preg_replace($patterns, $values, $routeMap[$pageName][$locale]);

            if (strpos($path, '{') !== false) {
                throw new \Exception('Unmatched url placeholder');
            }

            return $path;
        };
    }

    /**
     * @param string $assetDir
     * @param string $cacheDir
     * @return \Closure|string
     */
    public function getAssetClosure($assetDir, $cacheDir)
    {
        $basePath = $this->basePath;
        $this->assetCacheManagerFactory->setAssetDir($assetDir);
        $this->assetCacheManagerFactory->setCacheDir($cacheDir);
        $cacheManager = $this->assetCacheManagerFactory->createAssetCacheManager();

        return function ($asset) use ($basePath, $cacheManager) {
            if (!is_string($asset)) {
                trigger_error('The asset function only accepts string as a path to a resource.', E_USER_ERROR);

                return '';
            }

            return $basePath . '/' . $asset . '?v=' . $cacheManager->getCrc32($basePath . '/' . $asset);
        };
    }

    /**
     * @return \Closure
     */
    public function getDumpClosure()
    {
        return function ($var) {
            return VarDumper::dump($var);
        };
    }

    /**
     * @param string $assetDir
     * @return \Closure|string
     */
    public function getImageClosure($assetDir)
    {
        $imageFactory = $this->imageFactory;
        $imageFactory->setAssetDir($assetDir);

        return function ($image) use ($imageFactory) {
            return $imageFactory->getImage($image);
        };
    }
}
