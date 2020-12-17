<?php

namespace Unit\Middleware\Url;

use Prophecy\Prophet;
use TwigYard\Component\AppState;
use TwigYard\Middleware\Url\UrlMiddleware;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;

class UrlMiddlewareCest
{
    public function redirectOnNonCanonicalUrl(\UnitTester $I)
    {
        $mw = $this->getMw(false, true, 'example.com');
        $response = $mw(
            $this->getRequest('http', 'example.com', '/path'),
            new Response(),
            function () {}
        );
        $I->assertEquals('http://www.example.com/path', $response->getHeaderLine('location'));
        $I->assertEquals(301, $response->getStatusCode());
    }

    public function redirectToHttps(\UnitTester $I)
    {
        $mw = $this->getMw(true, true);
        $response = $mw(
            $this->getRequest('http', 'www.example.com', '/path'),
            new Response(),
            function () {}
        );
        $I->assertEquals('https://www.example.com/path', $response->getHeaderLine('location'));
        $I->assertEquals(301, $response->getStatusCode());
    }

    public function redirectOnNonCanonicalUrlWithParentDomain(\UnitTester $I)
    {
        $mw = $this->getMw(false, true, 'example.com', 'localhost');
        $response = $mw(
            $this->getRequest('http', 'example.com.localhost', '/path'),
            new Response(),
            function () {}
        );
        $I->assertEquals('http://www.example.com.localhost/path', $response->getHeaderLine('location'));
        $I->assertEquals(301, $response->getStatusCode());
    }

    public function redirectToHttpsWithParentDomain(\UnitTester $I)
    {
        $mw = $this->getMw(true, true, 'www.example.com', 'localhost');
        $response = $mw(
            $this->getRequest('http', 'www.example.com.localhost', '/path'),
            new Response(),
            function () {}
        );
        $I->assertEquals('https://www.example.com.localhost/path', $response->getHeaderLine('location'));
        $I->assertEquals(301, $response->getStatusCode());
    }

    public function doNotRedirectToHttpsIfDisabledGlobally(\UnitTester $I)
    {
        $mw = $this->getMw(true, false);
        $response = $mw($this->getRequest('http', 'www.example.com'), new Response(), function () {
            return true;
        });
        $I->assertTrue($response);
    }

    public function doNotRedirectToHttpsIfDisabledInSite(\UnitTester $I)
    {
        $mw = $this->getMw(false, true);
        $response = $mw($this->getRequest('http', 'www.example.com'), new Response(), function () {
            return true;
        });
        $I->assertTrue($response);
    }

    public function doNotRedirectToHttpsIfAlreadyHttps(\UnitTester $I)
    {
        $mw = $this->getMw(true, true);
        $response = $mw($this->getRequest('https', 'www.example.com'), new Response(), function () {
            return true;
        });
        $I->assertTrue($response);
    }

    private function getRequest(string $scheme, string $host, string $path = null): ServerRequest
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
     * @param string $host
     * @param string $parentDomain
     */
    private function getMw(
        bool $ssl = false,
        bool $sslAllowed = true,
        ?string $host = 'www.example.com',
        ?string $parentDomain = ''
    ): UrlMiddleware {
        $prophet = new Prophet();

        $appStateProph = $prophet->prophesize(AppState::class);
        $config = ['canonical' => 'www.example.com', 'extra' => ['example.com']];
        if ($ssl) {
            $config['ssl'] = true;
        }
        $appStateProph
            ->getMiddlewareConfig()
            ->willReturn(['url' => $config])
            ->shouldBeCalled();
        $appStateProph
            ->getHost()
            ->willReturn($host)
            ->shouldBeCalled();

        return new UrlMiddleware(
            $appStateProph->reveal(),
            $sslAllowed,
            $parentDomain
        );
    }
}
