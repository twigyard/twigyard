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
     * @var ImageFactory
     */
    private $imageFactory;

    /**
     * @var AssetCacheManagerFactory
     */
    private $assetCacheManagerFactory;

    /**
     * TemplatingClosureFactory constructor.
     */
    public function __construct(string $basePath, ImageFactory $imageFactory, AssetCacheManagerFactory $cacheManagerFactory)
    {
        $this->basePath = $basePath;
        $this->imageFactory = $imageFactory;
        $this->assetCacheManagerFactory = $cacheManagerFactory;
    }

    public function getPathClosure(array $routeMap, string $locale): \Closure
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

    public function getAssetClosure(string $assetDir, ?string $cacheDir): \Closure
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

    public function getDumpClosure(): \Closure
    {
        return function ($var) {
            return VarDumper::dump($var);
        };
    }

    public function getImageClosure(string $assetDir): \Closure
    {
        $imageFactory = $this->imageFactory;
        $imageFactory->setAssetDir($assetDir);

        return function ($image) use ($imageFactory) {
            return $imageFactory->getImage($image);
        };
    }
}
