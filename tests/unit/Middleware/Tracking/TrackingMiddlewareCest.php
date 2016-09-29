<?php

namespace Unit\Middleware\Tracking;

use TwigYard\Component\AppState;
use TwigYard\Middleware\Tracking\TrackingMiddleware;
use Prophecy\Argument\Token\AnyValueToken;
use Prophecy\Prophet;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;

class TrackingMiddlewareCest
{
    /**
     * @param UnitTester $I
     */
    public function noErrorOnConfigMissing(\UnitTester $I)
    {
        $prophet =  new Prophet();
        $appStateProph = $prophet->prophesize(AppState::class);
        $appStateProph->getConfig()->willReturn([]);

        $mw = new TrackingMiddleware($appStateProph->reveal(), true);
        $response = $mw(new ServerRequest(), new Response(), function () {
            return true;
        });
        $I->assertTrue($response);
    }

    /**
     * @param UnitTester $I
     */
    public function sendTrackingIdsToParamsIfEnabled(\UnitTester $I)
    {
        $prophet = new Prophet();
        $mw = $this->getMw($prophet);
        $callBackCalled = $mw(
            new ServerRequest(),
            new Response(),
            function (ServerRequestInterface $request, Response $response) use ($prophet, $I) {
                $prophet->checkPredictions();
                return true;
            }
        );
        $I->assertTrue($callBackCalled);
    }
    
    public function dontSendTrackingIdsParamsIfDisabled(\UnitTester $I)
    {
        $prophet = new Prophet();
        $mw = $this->getMw($prophet, false);
        $callBackCalled = $mw(
            new ServerRequest(),
            new Response(),
            function (ServerRequestInterface $request, Response $response) use ($prophet, $I) {
                $prophet->checkPredictions();
                return true;
            }
        );
        $I->assertTrue($callBackCalled);
    }

    /**
     * @param Prophet $prophet
     * @return \Middleware\Tracking\TrackingMiddleware
     */
    private function getMw(Prophet $prophet = null, $trackingEnabled = true)
    {
        $prophet = $prophet ? $prophet : new Prophet();
        $appStateProph = $prophet->prophesize(AppState::class);
        $appStateProph->getConfig()->willReturn(['tracking' => ['account_id' => 'ABC-456-7789']]);
        if ($trackingEnabled) {
            $appStateProph->setTrackingIds(['account_id' => 'ABC-456-7789'])->shouldBeCalled();
        } else {
            $appStateProph->setTrackingIds(new AnyValueToken())->shouldNotBeCalled();
        }

        return new TrackingMiddleware($appStateProph->reveal(), $trackingEnabled);
    }
}
