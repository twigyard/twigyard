<?php

namespace Unit\Middleware\Redirect;

use TwigYard\Component\AppState;
use TwigYard\Middleware\Redirect\RedirectMiddleware;
use Prophecy\Argument;
use Prophecy\Prophet;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Uri;

class RedirectMiddlewareCest
{
    /**
     * @param \UnitTester $I
     */
    public function noErrorOnConfigMissing(\UnitTester $I)
    {
        $prophet =  new Prophet();
        $appStateProph = $prophet->prophesize(AppState::class);
        $appStateProph->getConfig()->willReturn([]);

        $mw = new RedirectMiddleware($appStateProph->reveal());
        $response = $mw(new ServerRequest(), new Response(), function () {
            return true;
        });
        $I->assertTrue($response);
    }

    /**
     * @param \UnitTester $I
     */
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

    /**
     * @param \UnitTester $I
     */
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
     * @return \Middleware\Locale\LocaleMiddleware
     */
    private function getMw(Prophet $prophet = null)
    {
        $prophet = $prophet ? $prophet : new Prophet();
        $appStateProph = $prophet->prophesize(AppState::class);
        $appStateProph->getConfig()->willReturn(['redirect' => ['/url/old' => '/url/new']]);
            
        return new RedirectMiddleware($appStateProph->reveal());
    }
}
