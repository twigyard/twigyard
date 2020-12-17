<?php

namespace Unit\Middleware\Tracking;

use Prophecy\Argument\Token\AnyValueToken;
use Prophecy\Prophet;
use TwigYard\Component\AppState;
use TwigYard\Middleware\Tracking\TrackingMiddleware;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;

class TrackingMiddlewareCest
{
    /**
     * @param UnitTester $I
     */
    public function noErrorOnConfigMissing(\UnitTester $I)
    {
        $prophet = new Prophet();
        $appStateProph = $prophet->prophesize(AppState::class);
        $appStateProph->getMiddlewareConfig()->willReturn([]);

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
            function () use ($prophet) {
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
            function () use ($prophet) {
                $prophet->checkPredictions();

                return true;
            }
        );
        $I->assertTrue($callBackCalled);
    }

    /**
     * @param Prophet $prophet
     */
    private function getMw(Prophet $prophet = null, $trackingEnabled = true): TrackingMiddleware
    {
        $prophet = $prophet ? $prophet : new Prophet();
        $appStateProph = $prophet->prophesize(AppState::class);
        $appStateProph->getMiddlewareConfig()->willReturn(['tracking' => ['account_id' => 'ABC-456-7789']]);
        if ($trackingEnabled) {
            $appStateProph->setTrackingIds(['account_id' => 'ABC-456-7789'])->shouldBeCalled();
        } else {
            $appStateProph->setTrackingIds(new AnyValueToken())->shouldNotBeCalled();
        }

        return new TrackingMiddleware($appStateProph->reveal(), $trackingEnabled);
    }
}
