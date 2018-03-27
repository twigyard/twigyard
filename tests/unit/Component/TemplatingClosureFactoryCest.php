<?php

namespace TwigYard\Unit\Component;

use Codeception\Util\Stub;
use Gregwar\Image\Image;
use Prophecy\Prophet;
use TwigYard\Component\AssetCacheManager;
use TwigYard\Component\AssetCacheManagerFactory;
use TwigYard\Component\ImageFactory;
use TwigYard\Component\TemplatingClosureFactory;
use TwigYard\Exception\InvalidRouteException;

class TemplatingClosureCest
{
    /**
     * @param \UnitTester $I
     */
    public function assetClosure(\UnitTester $I)
    {
        $factory = new TemplatingClosureFactory(
            '/base',
            Stub::make(ImageFactory::class),
            $this->getAssetCacheManagerFactory()->reveal()
        );
        $closure = $factory->getAssetClosure('base', 'sites_var_cache');
        $I->assertEquals('/base/assets/styles.css?v=1234567890', $closure('assets/styles.css'));
    }

    /**
     * @param \UnitTester $I
     */
    public function pathClosure(\UnitTester $I)
    {
        $factory = new TemplatingClosureFactory(
            '/base',
            Stub::make(ImageFactory::class),
            $this->getAssetCacheManagerFactory()->reveal()
        );
        $closure = $factory->getPathClosure($this->getRouteMap(), 'cs_CZ');
        $I->assertEquals('/cs/page', $closure('page'));
    }

    /**
     * @param \UnitTester $I
     */
    public function pathClosureRedirectToIndex(\UnitTester $I)
    {
        $factory = new TemplatingClosureFactory(
            '/base',
            Stub::make(ImageFactory::class),
            $this->getAssetCacheManagerFactory()->reveal()
        );
        $closure = $factory->getPathClosure($this->getRouteMap(), 'en_US');
        $I->assertEquals('/en/index', $closure('page'));
    }

    /**
     * @param \UnitTester $I
     */
    public function pathClosureExceptionOnNonExistantPage(\UnitTester $I)
    {
        $I->seeExceptionThrown(InvalidRouteException::class, function () {
            $factory = new TemplatingClosureFactory(
                '/base',
                Stub::make(ImageFactory::class),
                $this->getAssetCacheManagerFactory()->reveal()
            );
            $closure = $factory->getPathClosure($this->getRouteMap(), 'en_US');
            $closure('invalid');
        });
    }

    /**
     * @param \UnitTester $I
     */
    public function pathClosureForceLocale(\UnitTester $I)
    {
        $factory = new TemplatingClosureFactory(
            '/base',
            Stub::make(ImageFactory::class),
            $this->getAssetCacheManagerFactory()->reveal()
        );
        $closure = $factory->getPathClosure($this->getRouteMap(), 'cs_CZ');
        $I->assertEquals('/en/index', $closure('index', [], 'en_US'));
    }

    /**
     * @param \UnitTester $I
     */
    public function pathClosureSetParam(\UnitTester $I)
    {
        $factory = new TemplatingClosureFactory(
            '/base',
            Stub::make(ImageFactory::class),
            $this->getAssetCacheManagerFactory()->reveal()
        );
        $closure = $factory->getPathClosure($this->getRouteMap(), 'en_US');
        $I->assertEquals('/en/products/abc', $closure('products', ['slug' => 'abc']));
    }

    /**
     * @param \UnitTester $I
     */
    public function imageClosure(\UnitTester $I)
    {
        $stub = Stub::make(ImageFactory::class, [
            'getImage' => Stub::once(function ($a) {
                if ($a === 'image') {
                    return new Image();
                }
            }),
            'setAssetDir' => Stub::once(function ($a) use ($I) {
                $I->assertEquals('web', $a);
            }),
        ]);
        $factory = new TemplatingClosureFactory(
            '/base',
            $stub,
            $this->getAssetCacheManagerFactory()->reveal()
        );
        $closure = $factory->getImageClosure('web');
        $I->assertEquals(new Image(), $closure('image'));
    }

    /**
     * @return \Prophecy\Prophecy\ObjectProphecy
     */
    private function getAssetCacheManagerFactory()
    {
        $prophet = new Prophet();
        $assetCacheManager = $prophet->prophesize(AssetCacheManager::class);
        $assetCacheManager->getCrc32('/base/assets/styles.css')->willReturn('1234567890');
        $assetCacheManagerFactory = $prophet->prophesize(AssetCacheManagerFactory::class);
        $assetCacheManagerFactory->setAssetDir('base')->shouldBeCalled();
        $assetCacheManagerFactory->setCacheDir('sites_var_cache')->shouldBeCalled();
        $assetCacheManagerFactory->createAssetCacheManager()->willReturn($assetCacheManager);

        return $assetCacheManagerFactory;
    }

    /**
     * @return array
     */
    private function getRouteMap()
    {
        return [
            'index' => [
                'cs_CZ' => '/cs/index',
                'en_US' => '/en/index',
            ],
            'page' => [
                'cs_CZ' => '/cs/page',
            ],
            'products' => [
                'en_US' => '/en/products/{slug}',
            ],
        ];
    }
}
