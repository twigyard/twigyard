<?php

namespace Unit\Middleware\Redirect;

use Prophecy\Prophet;
use Psr\Http\Message\ServerRequestInterface;
use TwigYard\Component\AppState;
use TwigYard\Middleware\Redirect\RedirectMiddleware;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Uri;

class RedirectMiddlewareCest
{
    public function noErrorOnConfigMissing(\UnitTester $I)
    {
        $prophet = new Prophet();
        $appStateProph = $prophet->prophesize(AppState::class);
        $appStateProph->getMiddlewareConfig()->willReturn([]);

        $mw = new RedirectMiddleware($appStateProph->reveal());
        $response = $mw(new ServerRequest(), new Response(), function () {
            return true;
        });
        $I->assertTrue($response);
    }

    public function redirectsIfMatch(\UnitTester $I)
    {
        $prophet = new Prophet();
        $mw = $this->getMw($prophet);
        $uri = $prophet->prophesize(Uri::class);
        $uri->getPath()->willReturn('/url/old');
        $serverRequest = $prophet->prophesize(ServerRequest::class);
        $serverRequest->getUri()->willReturn($uri->reveal());
        $response = $mw(
            $serverRequest->reveal(),
            new Response(),
            function (ServerRequestInterface $request, Response $response) use ($I) {
                $I->fail('redirect expected');
            }
        );

        $I->assertEquals(301, $response->getStatusCode());
        $I->assertEquals(['/url/new'], $response->getHeader('Location'));
    }

    public function noActionIfNotMatch(\UnitTester $I)
    {
        $prophet = new Prophet();
        $mw = $this->getMw($prophet);
        $uri = $prophet->prophesize(Uri::class);
        $uri->getPath()->willReturn('/invalid-url');
        $serverRequest = $prophet->prophesize(ServerRequest::class);
        $serverRequest->getUri()->willReturn($uri->reveal());
        $callBackCalled = $mw(
            $serverRequest->reveal(),
            new Response(),
            function (ServerRequestInterface $request, Response $response) use ($prophet, $I) {
                $prophet->checkPredictions();
                $I->assertNotEquals(301, $response->getStatusCode());

                return true;
            }
        );
        $I->assertTrue($callBackCalled);
    }

    /**
     * @param \Prophecy\Prophet $prophet
     */
    private function getMw(Prophet $prophet = null): RedirectMiddleware
    {
        $prophet = $prophet ? $prophet : new Prophet();
        $appStateProph = $prophet->prophesize(AppState::class);
        $appStateProph->getMiddlewareConfig()->willReturn(['redirect' => ['/url/old' => '/url/new']]);

        return new RedirectMiddleware($appStateProph->reveal());
    }
}
