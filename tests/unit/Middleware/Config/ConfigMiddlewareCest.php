<?php

namespace Unit\Middleware\Config;

use Prophecy\Argument;
use Prophecy\Argument\Token\AnyValueToken;
use Prophecy\Argument\Token\TypeToken;
use Prophecy\Prophecy\ObjectProphecy;
use Prophecy\Prophet;
use Psr\Http\Message\ServerRequestInterface;
use TwigYard\Component\AppState;
use TwigYard\Component\ConfigCacheInterface;
use TwigYard\Exception\InvalidSiteConfigException;
use TwigYard\Middleware\Config\ConfigMiddleware;
use VirtualFileSystem\FileSystem;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;

class ConfigMiddlewareCest
{
    /**
     * @throws InvalidSiteConfigException
     */
    public function configVersion2Valid(\UnitTester $I)
    {
        $fs = $this->getFs();
        $prophet = new Prophet();
        $request = $this->getRequest('http', 'www.example.com', '/images');
        $mw = $this->getMw(
            $fs,
            $this->getVersion2ConfigCacheProphecy($prophet),
            $prophet,
            null,
            $this->getAppStateProphecy($prophet, $fs, ['components_key' => 'components_value'])
        );
        $callBackCalled = $mw($request, new Response(), function (ServerRequestInterface $request) use ($I, $prophet) {
            $I->assertEquals($request->getUri()->getPath(), '/images');
            $prophet->checkPredictions();

            return true;
        });
        $I->assertTrue($callBackCalled);
    }

    /**
     * @throws InvalidSiteConfigException
     */
    public function configVersion1Valid(\UnitTester $I)
    {
        $fs = $this->getFs();
        $prophet = new Prophet();
        $request = $this->getRequest('http', 'www.example.com', '/images');
        $mw = $this->getMw($fs, null, $prophet, null);
        $callBackCalled = $mw($request, new Response(), function (ServerRequestInterface $request) use ($I, $prophet) {
            $I->assertEquals($request->getUri()->getPath(), '/images');
            $prophet->checkPredictions();

            return true;
        });
        $I->assertTrue($callBackCalled);
    }

    /**
     * @throws InvalidSiteConfigException
     */
    public function return404OnNoSiteDir(\UnitTester $I)
    {
        $fs = new FileSystem();
        $fs->createDirectory('/sites');
        $prophet = new Prophet();
        $configCacheProph = $prophet->prophesize(ConfigCacheInterface::class);
        $configCacheProph->getConfig(new AnyValueToken(), new AnyValueToken())->willReturn([]);
        $mw = $this->getMw($fs, $configCacheProph);
        $response = $mw($this->getRequest('http', 'www.example.com'), new Response(), function () {});
        $I->assertEquals($response->getStatusCode(), 404);
    }

    /**
     * @throws InvalidSiteConfigException
     */
    public function noExceptionOnNoSiteYml()
    {
        $fs = new FileSystem();
        $fs->createDirectory('/sites/www.example.com', true);
        $prophet = new Prophet();
        $appStateProph = $prophet->prophesize(AppState::class);
        $appStateProph->setMiddlewareConfig(Argument::any())->shouldNotBeCalled();
        $appStateProph->setComponentConfig(Argument::any())->shouldNotBeCalled();
        $appStateProph->setHost(Argument::any())->shouldNotBeCalled();
        $cache = $prophet->prophesize(ConfigCacheInterface::class);
        $cache->getConfig(new AnyValueToken(), new AnyValueToken())->willReturn([]);
        $mw = new ConfigMiddleware(
            $appStateProph->reveal(),
            $cache->reveal(),
            $fs->path('/sites'),
            'site.yml',
            false
        );
        $mw($this->getRequest('http', 'www.example.com'), new Response(), function () {});
        $prophet->checkPredictions();
    }

    /**
     * @throws InvalidSiteConfigException
     */
    public function ignoreOtherInvalidSiteYml()
    {
        $fs = new FileSystem();
        $fs->createDirectory('/sites/www.example.com', true);
        $fs->createFile('/sites/www.example.com/site.yml');

        $prophet = new Prophet();
        $appStateProph = $prophet->prophesize(AppState::class);
        $configCacheProph = $prophet->prophesize(ConfigCacheInterface::class);
        $configCacheProph->getConfig(new AnyValueToken(), new AnyValueToken())->willReturn([]);
        $configCache = $configCacheProph;

        $mw = $this->getMw($fs, $configCache, $prophet, null, $appStateProph);
        $mw($this->getRequest('http', 'www.example2.com'), new Response(), function () {
        });
        $prophet->checkPredictions();
    }

    public function errorIfInvalidSiteYml(\UnitTester $I)
    {
        $fs = new FileSystem();
        $fs->createDirectory('/sites/www.example.com', true);
        $fs->createFile('/sites/www.example.com/site.yml');

        $prophet = new Prophet();
        $appStateProph = $prophet->prophesize(AppState::class);
        $configCacheProph = $prophet->prophesize(ConfigCacheInterface::class);
        $configCacheProph->getConfig(new AnyValueToken(), new AnyValueToken())->willReturn([]);
        $configCache = $configCacheProph;

        $mw = $this->getMw($fs, $configCache, $prophet, null, $appStateProph);
        $I->expectThrowable(InvalidSiteConfigException::class, function () use ($mw) {
            $mw($this->getRequest('http', 'www.example.com'), new Response(), function () {
            });
        });
    }

    /**
     * @throws InvalidSiteConfigException
     */
    public function allowSubdomainAccessIfDefined(\UnitTester $I)
    {
        $fs = $this->getFs();
        $prophet = new Prophet();
        $mw = $this->getMw($fs, null, $prophet, 'example');
        $request = $this->getRequest('http', 'www.example.com.example');
        $callBackCalled = $mw($request, new Response(), function () use ($prophet) {
            $prophet->checkPredictions();

            return true;
        });
        $I->assertTrue($callBackCalled);
    }

    /**
     * @throws InvalidSiteConfigException
     */
    public function disallowSubdomainAccessIfNotDefined(\UnitTester $I)
    {
        $fs = $this->getFs();
        $prophet = new Prophet();
        $mw = $this->getMw($fs, null, $prophet, '');
        $request = $this->getRequest('http', 'www.example.com.example');
        $response = $mw($request, new Response(), function () {});
        $I->assertEquals($response->getStatusCode(), 404);

        $mw = $this->getMw($fs, null, $prophet, null);
        $response = $mw($request, new Response(), function () {});
        $I->assertEquals($response->getStatusCode(), 404);
    }

    /**
     * @return \VirtualFileSystem\FileSystem
     */
    private function getFs()
    {
        return new FileSystem();
    }

    /**
     * @param $scheme
     * @param string $host
     * @param null $path
     * @return ServerRequest
     */
    private function getRequest($scheme, $host, $path = null)
    {
        $uri = (new \Zend\Diactoros\Uri())
            ->withHost($host)
            ->withScheme($scheme);

        if ($path) {
            $uri = $uri->withPath($path);
        }

        return (new ServerRequest(['SCRIPT_NAME' => '/app.php', 'REMOTE_ADDR' => '127.0.1.2']))->withUri($uri);
    }

    /**
     * @param \TwigYard\Component\ConfigCacheInterface $configCache
     * @param string $devDomain
     * @return ConfigMiddleware
     */
    private function getMw(
        FileSystem $fs,
        ObjectProphecy $configCache = null,
        Prophet $prophet = null,
        $devDomain = 'dev.domain',
        ObjectProphecy $appStateProph = null
    ) {
        $prophet = $prophet ?: new Prophet();
        $appStateProph = $appStateProph ?: $this->getAppStateProphecy($prophet, $fs);
        $configCache = $configCache ?: $this->getVersion1ConfigCacheProphecy($prophet);

        return new ConfigMiddleware(
            $appStateProph->reveal(),
            $configCache->reveal(),
            $fs->path('/sites'),
            'site.yml',
            $devDomain
        );
    }

    /**
     * @return mixed
     */
    private function getVersion1ConfigCacheProphecy(Prophet $prophet): ObjectProphecy
    {
        $siteConfig = ['middlewares_key' => 'middlewares_value'];

        $configCacheProph = $prophet->prophesize(ConfigCacheInterface::class);
        $configCacheProph->getConfig(new AnyValueToken(), new AnyValueToken())->willReturn([
            'www.example.com' => $siteConfig,
            'example.com' => $siteConfig,
        ]);

        return $configCacheProph;
    }

    /**
     * @return mixed
     */
    private function getVersion2ConfigCacheProphecy(Prophet $prophet): ObjectProphecy
    {
        $siteConfig = [
            'version' => 2,
            'middlewares' => [
                'middlewares_key' => 'middlewares_value',
            ],
            'components' => [
                'components_key' => 'components_value',
            ],
        ];

        $configCacheProph = $prophet->prophesize(ConfigCacheInterface::class);
        $configCacheProph->getConfig(new AnyValueToken(), new AnyValueToken())->willReturn([
            'www.example.com' => $siteConfig,
            'example.com' => $siteConfig,
        ]);

        return $configCacheProph;
    }

    private function getAppStateProphecy(
        Prophet $prophet,
        FileSystem $fs,
        array $componentConfig = null
    ): ObjectProphecy {
        $appStateProph = $prophet->prophesize(AppState::class);
        $appStateProph
            ->setMiddlewareConfig(['middlewares_key' => 'middlewares_value'])
            ->willReturn($appStateProph)
            ->shouldBeCalled();
        $appStateProph
            ->setComponentConfig($componentConfig ?: [])
            ->willReturn($appStateProph)
            ->shouldBeCalled();
        $appStateProph
            ->setScheme(new TypeToken('string'))
            ->willReturn($appStateProph)
            ->shouldBeCalled();
        $appStateProph
            ->setHost('www.example.com')
            ->willReturn($appStateProph)
            ->shouldBeCalled();
        $appStateProph
            ->setRemoteIp(new TypeToken('string'))
            ->willReturn($appStateProph)
            ->shouldBeCalled();

        return $appStateProph;
    }
}
