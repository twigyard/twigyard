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
        $response = $mw($this->getRequest(), new Response(), function () {
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
    public function allowsPassOnCorrectIpAddress(\UnitTester $I)
    {
        $mw = $this->getMw(['127.0.0.1']);
        $response = $mw($this->getRequest(), new Response(), function () use ($I) {
            return true;
        });
        $I->assertTrue($response);
    }

    /**
     * @param \UnitTester $I
     */
    public function promptsIfWrongIpAddress(\UnitTester $I)
    {
        $mw = $this->getMw(['127.0.0.254']);
        $response = $mw($this->getRequest(), new Response(), function () {
        });
        $I->assertTrue($response->hasHeader('WWW-Authenticate'));
        $I->assertEquals(401, $response->getStatusCode());
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
     * @param string|null $user
     * @param string|null $pass
     * @return ServerRequest
     */
    private function getRequest($user = null, $pass = null)
    {
        $request = (new ServerRequest(['REMOTE_ADDR' => '127.0.0.1']));

        if (isset($user) && isset($pass)) {
            $authString = 'Basic ' . base64_encode($user . ':' . $pass);

            $request = $request
                ->withQueryParams(['httpauth' => $authString])
                ->withAttribute('site.config', ['httpauth' => ['username' => 'user', 'password' => 'pass']]);
        }

        return $request;
    }

    /**
     * @return \TwigYard\Middleware\Httpauth\HttpauthMiddleware
     */
    private function getMw($excludeIpAddresses = [])
    {
        $prophet = new Prophet();
        $appStateProph = $prophet->prophesize(AppState::class);
        $appStateProph->getConfig()->willReturn(['httpauth' => [
            'username' => 'user',
            'password' => 'pass',
            'exclude_ip_addresses' => $excludeIpAddresses,
        ]]);

        return new HttpauthMiddleware($appStateProph->reveal(), 'data');
    }
}
