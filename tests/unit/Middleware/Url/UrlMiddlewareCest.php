<?php

namespace Unit\Middleware\Data;

use TwigYard\Component\AppState;
use TwigYard\Component\ConfigCacheInterface;
use TwigYard\Exception\InvalidSiteConfigException;
use TwigYard\Middleware\Url\UrlMiddleware;
use Prophecy\Argument;
use Prophecy\Argument\Token\AnyValueToken;
use Prophecy\Argument\Token\TypeToken;
use Prophecy\Prophecy\ObjectProphecy;
use Prophecy\Prophet;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Yaml\Yaml;
use VirtualFileSystem\FileSystem;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;

class UrlMiddlewareCest
{
    /**
     * @param \UnitTester $I
     */
    public function requestAttributesSet(\UnitTester $I)
    {
        $prophet = new Prophet();
        $fs = $this->getFs();
        $mw = $this->getMw($fs, null, $prophet, null);
        $callBackCalled = $mw($this->getRequest('www.example.com'), new Response(), function () use ($prophet) {
            $prophet->checkPredictions();
            return true;
        });
        $I->assertTrue($callBackCalled);
    }

    /**
     * @param \UnitTester $I
     */
    public function pathCorrected(\UnitTester $I)
    {
        $fs = $this->getFs();
        $prophet = new Prophet();

        $request = $this->getRequest('www.example.com');
        $request = $request->withUri($request->getUri()->withPath('/images'));

        $mw = $this->getMw($fs, null, $prophet, null);
        $callBackCalled = $mw($request, new Response(), function (ServerRequestInterface $request) use ($I, $prophet) {
            $I->assertEquals($request->getUri()->getPath(), '/images');
            $prophet->checkPredictions();
            return true;
        });
        $I->assertTrue($callBackCalled);
    }

    /**
     * @param \UnitTester $I
     */
    public function redirectOnNonCanonicalUrl(\UnitTester $I)
    {
        $fs = $this->getFs();
        $mw = $this->getMw($fs, null, null, null);
        $response = $mw($this->getRequest('example.com'), new Response(), function () {
        });
        $I->assertEquals($response->getHeaderLine('location'), 'http://www.example.com');
        $I->assertEquals($response->getStatusCode(), 301);
    }

    /**
     * @param \UnitTester $I
     */
    public function return404OnNoSiteDir(\UnitTester $I)
    {
        $fs = new FileSystem();
        $fs->createDirectory('/sites');
        $prophet = new Prophet();
        $configCacheProph = $prophet->prophesize(ConfigCacheInterface::class);
        $configCacheProph->getConfig(new AnyValueToken(), new AnyValueToken())->willReturn([]);
        $mw = $this->getMw($fs, $configCacheProph->reveal());
        $response = $mw($this->getRequest('www.example.com'), new Response(), function () {
        });
        $I->assertEquals($response->getStatusCode(), 404);
    }

    public function noExceptionOnNoSiteYml()
    {
        $fs = new FileSystem();
        $fs->createDirectory('/sites/www.example.com', true);
        $prophet = new Prophet();
        $appStateProph = $prophet->prophesize(AppState::class);
        $appStateProph->setConfig(Argument::any())->shouldNotBeCalled();
        $appStateProph->setSiteDir(Argument::any())->shouldNotBeCalled();
        $cache = $prophet->prophesize(ConfigCacheInterface::class);
        $cache->getConfig(new AnyValueToken(), new AnyValueToken())->willReturn([]);
        $mw = new UrlMiddleware(
            $appStateProph->reveal(),
            $cache->reveal(),
            $fs->path('/sites'),
            'site.yml',
            'parameters.yml',
            false,
            'devdomain'
        );
        $mw($this->getRequest('www.example.com'), new Response(), function () {
        });
        $prophet->checkPredictions();
    }

    public function ignoreOtherInvalidSiteYml()
    {
        $fs = new FileSystem();
        $fs->createDirectory('/sites/www.example.com', true);
        $fs->createFile('/sites/www.example.com/site.yml');

        $prophet = new Prophet();
        $appStateProph = $prophet->prophesize(AppState::class);
        $configCacheProph = $prophet->prophesize(ConfigCacheInterface::class);
        $configCacheProph->getConfig(new AnyValueToken(), new AnyValueToken())->willReturn([]);
        $configCache = $configCacheProph->reveal();

        $mw = $this->getMw($fs, $configCache, $prophet, null, $appStateProph);
        $mw($this->getRequest('www.example2.com'), new Response(), function () {
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
        $configCache = $configCacheProph->reveal();

        $mw = $this->getMw($fs, $configCache, $prophet, null, $appStateProph);
        $I->expectException(InvalidSiteConfigException::class, function () use ($mw) {
            $mw($this->getRequest('www.example.com'), new Response(), function () {
            });
        });
    }

    /**
     * @param \UnitTester $I
     */
    public function allowSubdomainAccessIfDefined(\UnitTester $I)
    {
        $fs = $this->getFs();
        $prophet = new Prophet();
        $mw = $this->getMw($fs, null, $prophet, 'example');
        $request = $this->getRequest('www.example.com.example');
        $callBackCalled = $mw($request, new Response(), function () use ($prophet) {
            $prophet->checkPredictions();
            return true;
        });
        $I->assertTrue($callBackCalled);
    }

    public function disallowSubdomainAccessIfNotDefined(\UnitTester $I)
    {
        $fs = $this->getFs();
        $prophet = new Prophet();
        $mw = $this->getMw($fs, null, $prophet, '');
        $request = $this->getRequest('www.example.com.example');
        $response = $mw($request, new Response(), function () use ($prophet) {
        });
        $I->assertEquals($response->getStatusCode(), 404);

        $mw = $this->getMw($fs, null, $prophet, null);
        $response = $mw($request, new Response(), function () use ($prophet) {
        });
        $I->assertEquals($response->getStatusCode(), 404);
    }

    public function setSiteSpecificParameters()
    {
        $prophet = new Prophet();
        $fs = $this->getFs();
        $fs->createDirectory('/sites/www.example.com', true);
        $siteParameters = <<<EOT
parameters:
    site_specific_param: value
EOT;
        $fs->createFile('/sites/www.example.com/parameters.yml', $siteParameters);
        $appStateProph = $prophet->prophesize(AppState::class);
        $appStateProph
            ->setConfig(['url' => ['canonical' => 'www.example.com', 'extra' => ['example.com']]])
            ->willReturn($appStateProph)
            ->shouldBeCalled();
        $appStateProph
            ->setSiteDir($fs->path('/sites/www.example.com'))
            ->willReturn($appStateProph)
            ->shouldBeCalled();
        $appStateProph
            ->getSiteDir()
            ->willReturn($fs->path('/sites/www.example.com'))
            ->shouldBeCalled();
        $appStateProph
            ->setSiteParameters(['site_specific_param' => 'value'])
            ->willReturn($appStateProph)
            ->shouldBeCalled();
        $appStateProph
            ->setRemoteIp(new TypeToken('string'))
            ->willReturn($appStateProph)
            ->shouldBeCalled();

        $mw = $this->getMw($fs, null, $prophet, null, $appStateProph);
        $mw($this->getRequest('www.example.com'), new Response(), function () {
        });
        $prophet->checkPredictions();
    }

    /**
     * @return \VirtualFileSystem\FileSystem
     */
    private function getFs()
    {
        $fs = new FileSystem();
        return $fs;
    }

    /**
     * @param string $host
     * @return ServerRequest
     */
    private function getRequest($host)
    {
        $uri = (new \Zend\Diactoros\Uri())
            ->withHost($host)
            ->withScheme('http');

        return (new ServerRequest(['SCRIPT_NAME' => '/app.php', 'REMOTE_ADDR' => '127.0.1.2']))->withUri($uri);
    }

    /**
     * @param \VirtualFileSystem\FileSystem $fs
     * @param \TwigYard\Component\ConfigCacheInterface $configCache
     * @param \Prophecy\Prophet|null $prophet
     * @param string $devDomain
     * @param ObjectProphecy|null $appStateProph
     * @return \TwigYard\Middleware\Url\UrlMiddleware
     */
    private function getMw(
        FileSystem $fs,
        ConfigCacheInterface $configCache = null,
        $prophet = null,
        $devDomain = 'dev.domain',
        ObjectProphecy $appStateProph = null
    ) {
        $prophet = $prophet ? $prophet : new Prophet();

        if ($appStateProph === null) {
            $appStateProph = $prophet->prophesize(AppState::class);
            $appStateProph
                ->setConfig(['url' => ['canonical' => 'www.example.com', 'extra' => ['example.com']]])
                ->willReturn($appStateProph)
                ->shouldBeCalled();
            $appStateProph
                ->setSiteDir($fs->path('/sites/www.example.com'))
                ->willReturn($appStateProph)
                ->shouldBeCalled();
            $appStateProph
                ->getSiteDir()
                ->willReturn($fs->path('/sites/www.example.com'))
                ->shouldBeCalled();
            $appStateProph
                ->setSiteParameters(new TypeToken('array'))
                ->willReturn($appStateProph)
                ->shouldBeCalled();
            $appStateProph
                ->setRemoteIp(new TypeToken('string'))
                ->willReturn($appStateProph)
                ->shouldBeCalled();
        }

        if (!$configCache) {
            $config = <<<EOT
url:
    canonical: www.example.com
    extra: [ example.com ]
EOT;
            $parsedConfig = Yaml::parse($config);
            $configCacheProph = $prophet->prophesize(ConfigCacheInterface::class);
            $configCacheProph->getConfig(new AnyValueToken(), new AnyValueToken())->willReturn([
                'www.example.com' => $parsedConfig,
                'example.com' => $parsedConfig,
            ]);
            $configCache = $configCacheProph->reveal();
        }

        return new UrlMiddleware(
            $appStateProph->reveal(),
            $configCache,
            $fs->path('/sites'),
            'site.yml',
            'parameters.yml',
            $devDomain
        );
    }
}
