<?php

namespace Unit\Middleware\Httpauth;

use Prophecy\Prophet;
use TwigYard\Component\AppState;
use TwigYard\Middleware\Httpauth\HttpauthMiddleware;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;

class HttpauthMiddlewareCest
{
    /**
     * @param \UnitTester $I
     */
    public function promptsIfNoAuthHeader(\UnitTester $I)
    {
        $mw = $this->getMw();
        $response = $mw(new ServerRequest(), new Response(), function () {
        });
        $I->assertTrue($response->hasHeader('WWW-Authenticate'));
        $I->assertEquals(401, $response->getStatusCode());
    }

    /**
     * @param \UnitTester $I
     */
    public function promptsIfWrongCredentials(\UnitTester $I)
    {
        $mw = $this->getMw();
        $response = $mw($this->getRequest('user', 'wrongpass'), new Response(), function () {
        });
        $I->assertTrue($response->hasHeader('WWW-Authenticate'));
        $I->assertEquals(401, $response->getStatusCode());
    }

    /**
     * @param \UnitTester $I
     */
    public function allowsPassOnCorrectCredentials(\UnitTester $I)
    {
        $mw = $this->getMw();
        $response = $mw($this->getRequest('user', 'pass'), new Response(), function () use ($I) {
            return true;
        });
        $I->assertTrue($response);
    }

    /**
     * @param \UnitTester $I
     */
    public function noErrorOnConfigMissing(\UnitTester $I)
    {
        $prophet = new Prophet();
        $appStateProph = $prophet->prophesize(AppState::class);
        $appStateProph->getConfig()->willReturn([]);
        $mw = new HttpauthMiddleware($appStateProph->reveal(), 'data');
        $response = $mw(new ServerRequest(), new Response(), function () use ($I) {
            return true;
        });
        $I->assertTrue($response);
    }

    /**
     * @return \Zend\Diactoros\ServerRequest
     */
    private function getRequest($user, $pass)
    {
        $authString = 'Basic ' . base64_encode($user . ':' . $pass);

        return (new ServerRequest())
            ->withQueryParams(['httpauth' => $authString])
            ->withAttribute('site.config', ['httpauth' => ['username' => 'user', 'password' => 'pass']]);
    }

    /**
     * @return \TwigYard\Middleware\Httpauth\HttpauthMiddleware
     */
    private function getMw()
    {
        $prophet = new Prophet();
        $appStateProph = $prophet->prophesize(AppState::class);
        $appStateProph->getConfig()->willReturn(['httpauth' => ['username' => 'user', 'password' => 'pass']]);

        return new HttpauthMiddleware($appStateProph->reveal(), 'data');
    }
}
