<?php

namespace Unit\Middleware\Httpauth;

use Prophecy\Prophet;
use Psr\Http\Message\ServerRequestInterface;
use TwigYard\Component\AppState;
use TwigYard\Middleware\Header\HeaderMiddleware;
use Zend\Diactoros\Response;

class HeaderMiddlewareCest
{
    /**
     * @param \UnitTester $I
     */
    public function injectDefaultHeaders(\UnitTester $I)
    {
        $prophet = new Prophet();
        $config = [];

        $mw = $this->getMw($prophet, $config);
        $serverRequest = $prophet->prophesize(ServerRequestInterface::class);

        $mw(
            $serverRequest->reveal(),
            new Response(),
            function (ServerRequestInterface $request, Response $response) use ($I) {
                $I->assertEquals(
                    [
                        'Content-Security-Policy' => ['default-src * \'unsafe-inline\' \'unsafe-eval\';'],
                        'Referrer-Policy' => ['strict-origin'],
                        'X-Content-Type-Options' => ['nosniff'],
                    ],
                    $response->getHeaders()
                );
            }
        );
    }

    /**
     * @param \UnitTester $I
     */
    public function injectDefaultSecureHeaders(\UnitTester $I)
    {
        $prophet = new Prophet();
        $config = [];

        $mw = $this->getMw($prophet, $config, 'https');
        $serverRequest = $prophet->prophesize(ServerRequestInterface::class);

        $mw(
            $serverRequest->reveal(),
            new Response(),
            function (ServerRequestInterface $request, Response $response) use ($I) {
                $I->assertEquals(
                    [
                        'Content-Security-Policy' => ['default-src https: \'unsafe-inline\' \'unsafe-eval\';'],
                        'Referrer-Policy' => ['strict-origin'],
                        'X-Content-Type-Options' => ['nosniff'],
                    ],
                    $response->getHeaders()
                );
            }
        );
    }

    /**
     * @param \UnitTester $I
     */
    public function doNotInjectGloballyDisabledHeaders(\UnitTester $I)
    {
        $prophet = new Prophet();
        $config = ['header' => null];

        $mw = $this->getMw($prophet, $config);
        $serverRequest = $prophet->prophesize(ServerRequestInterface::class);

        $mw(
            $serverRequest->reveal(),
            new Response(),
            function (ServerRequestInterface $request, Response $response) use ($I) {
                $I->assertEquals(
                    [],
                    $response->getHeaders()
                );
            }
        );
    }

    /**
     * @param \UnitTester $I
     */
    public function doNotInjectIndividuallyDisabledHeaders(\UnitTester $I)
    {
        $prophet = new Prophet();
        $config = [
            'header' => [
                'Content-Security-Policy' => null,
                'Referrer-Policy' => null,
                'X-Content-Type-Options' => null,
            ],
        ];

        $mw = $this->getMw($prophet, $config);
        $serverRequest = $prophet->prophesize(ServerRequestInterface::class);

        $mw(
            $serverRequest->reveal(),
            new Response(),
            function (ServerRequestInterface $request, Response $response) use ($I) {
                $I->assertEquals(
                    [],
                    $response->getHeaders()
                );
            }
        );
    }

    /**
     * @param \UnitTester $I
     */
    public function injectAllHeaders(\UnitTester $I)
    {
        $prophet = new Prophet();
        $config = [
            'header' => [
                'Content-Security-Policy' => [
                    'default-src' => ['\'self\''],
                    'img-src' => ['\'self\'', 'http://www.example.com'],
                ],
                'Referrer-Policy' => 'same-origin',
                'X-Content-Type-Options' => 'nosniff',
            ],
        ];

        $mw = $this->getMw($prophet, $config);
        $serverRequest = $prophet->prophesize(ServerRequestInterface::class);

        $mw(
            $serverRequest->reveal(),
            new Response(),
            function (ServerRequestInterface $request, Response $response) use ($I) {
                $I->assertEquals(
                    [
                        'Content-Security-Policy' => ['default-src \'self\'; img-src \'self\' http://www.example.com;'],
                        'Referrer-Policy' => ['same-origin'],
                        'X-Content-Type-Options' => ['nosniff'],
                    ],
                    $response->getHeaders()
                );
            }
        );
    }

    /**
     * @param Prophet $prophet
     * @param $config
     * @param string $scheme
     * @return HeaderMiddleware
     */
    private function getMw(Prophet $prophet, $config, $scheme = 'http')
    {
        $prophet = $prophet ? $prophet : new Prophet();
        $appStateProph = $prophet->prophesize(AppState::class);
        $appStateProph->getConfig()->willReturn($config);
        $appStateProph->getScheme()->willReturn($scheme);

        return new HeaderMiddleware($appStateProph->reveal());
    }
}
