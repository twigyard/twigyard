<?php

namespace Unit\Middleware\Router;

use Prophecy\Prophet;
use TwigYard\Component\AppState;
use TwigYard\Middleware\Router\RouterMiddleware;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Uri;

class RouterMiddlewareCest
{
    public function noErrorOnConfigMissing(\UnitTester $I)
    {
        $prophet = new Prophet();
        $appStateProph = $prophet->prophesize(AppState::class);
        $appStateProph->getMiddlewareConfig()->willReturn([]);

        $mw = new RouterMiddleware($appStateProph->reveal());
        $response = $mw(new ServerRequest(), new Response(), function () {
            return true;
        });
        $I->assertTrue($response);
    }

    public function return404onUnmatchedUrlMultilang(\UnitTester $I)
    {
        $request = (new ServerRequest())->withUri((new Uri('/invalidPage')));
        $mw = $this->getMwMultilang(null, ['router' => ['page1' => ['cs_CZ' => '/page1-cs']]]);
        $response = $mw($request, new Response(), function () {
        });
        $I->assertEquals($response->getStatusCode(), 404);
    }

    public function return404onUnmatchedUrlSinglelang(\UnitTester $I)
    {
        $request = (new ServerRequest())->withUri((new Uri('/invalidPage')));
        $mw = $this->getMwSinglelang(null, ['router' => ['page1' => '/page1']]);
        $response = $mw($request, new Response(), function () {
        });
        $I->assertEquals($response->getStatusCode(), 404);
    }

    public function correctUrlParamsNoValidateMultilang(\UnitTester $I)
    {
        $prophet = new Prophet();
        $request = (new ServerRequest())->withUri((new Uri('/page1-cs/xxx')));
        $mw = $this->getMwMultilang(
            $prophet,
            ['router' => ['page1' => ['cs_CZ' => '/page1-cs/{var1}']]]
        );
        $callBackCalled = $mw($request, new Response(), function () use ($prophet) {
            $prophet->checkPredictions();

            return true;
        });
        $I->assertTrue($callBackCalled);
    }

    public function correctUrlParamsNoValidateSinglelang(\UnitTester $I)
    {
        $prophet = new Prophet();
        $request = (new ServerRequest())->withUri((new Uri('/page1/xxx')));
        $mw = $this->getMwSinglelang($prophet, ['router' => ['page1' => '/page1/{var1}']]);
        $callBackCalled = $mw($request, new Response(), function () use ($prophet) {
            $prophet->checkPredictions();

            return true;
        });
        $I->assertTrue($callBackCalled);
    }

    public function correctUrlParamsValidateMultilang(\UnitTester $I)
    {
        $prophet = new Prophet();
        $request = (new ServerRequest())->withUri((new Uri('/page1-cs/xxx')));
        $mw = $this->getMwMultilang(
            $prophet,
            ['router' => ['page1' => ['cs_CZ' => '/page1-cs/{var1 | dataSet:element_1.subelement_A}']]],
            ['dataSet' => [['element_1' => ['subelement_A' => 'xxx']]]]
        );
        $callBackCalled = $mw($request, new Response(), function () use ($prophet) {
            $prophet->checkPredictions();

            return true;
        });
        $I->assertTrue($callBackCalled);
    }

    public function correctUrlParamsValidateSinglelang(\UnitTester $I)
    {
        $prophet = new Prophet();
        $request = (new ServerRequest())->withUri((new Uri('/page1/xxx')));
        $mw = $this->getMwSinglelang(
            $prophet,
            ['router' => ['page1' => '/page1/{var1 | dataSet:element_1.subelement_A}']],
            ['dataSet' => [['element_1' => ['subelement_A' => 'xxx']]]]
        );
        $callBackCalled = $mw($request, new Response(), function () use ($prophet) {
            $prophet->checkPredictions();

            return true;
        });
        $I->assertTrue($callBackCalled);
    }

    public function return404onInvalidUrlParamMultilang(\UnitTester $I)
    {
        $prophet = new Prophet();
        $request = (new ServerRequest())->withUri((new Uri('/page1-cs/xxx')));
        $mw = $this->getMwMultilang(
            $prophet,
            ['router' => ['page1' => ['cs_CZ' => '/page1-cs/{var1 | dataSet:element_1.subelement_A}']]],
            ['dataSet' => [['element_1' => ['subelement_A' => 'yyy']]]]
        );
        $response = $mw($request, new Response(), function () {
        });
        $I->assertEquals($response->getStatusCode(), 404);
    }

    public function return404onInvalidUrlParamSinglelang(\UnitTester $I)
    {
        $prophet = new Prophet();
        $request = (new ServerRequest())->withUri((new Uri('/page1/xxx')));
        $mw = $this->getMwSinglelang(
            $prophet,
            ['router' => ['page1' => '/page1/{var1 | dataSet:element_1.subelement_A}']],
            ['dataSet' => [['element_1' => ['subelement_A' => 'yyy']]]]
        );
        $response = $mw($request, new Response(), function () {
        });
        $I->assertEquals($response->getStatusCode(), 404);
    }

    public function return404onNonExistentDataFileMultilang(\UnitTester $I)
    {
        $prophet = new Prophet();
        $request = (new ServerRequest())->withUri((new Uri('/page1-cs/xxx')));
        $mw = $this->getMwMultilang(
            $prophet,
            ['router' => ['page1' => ['cs_CZ' => '/page1-cs/{var1 | dataSet:element_1.subelement_A}']]],
            []
        );
        $response = $mw($request, new Response(), function () {
        });
        $I->assertEquals($response->getStatusCode(), 404);
    }

    public function return404onNonExistentDataFileSinglelang(\UnitTester $I)
    {
        $prophet = new Prophet();
        $request = (new ServerRequest())->withUri((new Uri('/page1/xxx')));
        $mw = $this->getMwSinglelang(
            $prophet,
            ['router' => ['page1' => '/page1/{var1 | dataSet:element_1.subelement_A}']],
            []
        );
        $response = $mw($request, new Response(), function () {
        });
        $I->assertEquals($response->getStatusCode(), 404);
    }

    public function no404onNonStringDataFileParamMultiLang(\UnitTester $I)
    {
        $prophet = new Prophet();
        $request = (new ServerRequest())->withUri((new Uri('/page1-cs/12')));
        $mw = $this->getMwMultilang(
            $prophet,
            ['router' => ['page1' => ['cs_CZ' => '/page1-cs/{var1 | dataSet:element_1.subelement_A}']]],
            ['dataSet' => [['element_1' => ['subelement_A' => 12]]]],
            12
        );
        $response = $mw($request, new Response(), function () {
            return true;
        });
        $I->assertTrue($response);
    }

    public function no404onNonStringDataFileParamSingleLang(\UnitTester $I)
    {
        $prophet = new Prophet();
        $request = (new ServerRequest())->withUri((new Uri('/page1/12')));
        $mw = $this->getMwSinglelang(
            $prophet,
            ['router' => ['page1' => '/page1/{var1 | dataSet:element_1.subelement_A}']],
            ['dataSet' => [['element_1' => ['subelement_A' => 12]]]],
            12
        );
        $response = $mw($request, new Response(), function () {
            return true;
        });
        $I->assertTrue($response);
    }

    /**
     * @param \Prophecy\Prophet $prophet
     * @return \Middleware\Router\RouterMiddleware
     */
    private function getMwMultilang(Prophet $prophet = null, $config = [], $data = null, $urlParam = 'xxx')
    {
        $prophet = $prophet ? $prophet : new Prophet();
        $appStateProph = $prophet->prophesize(AppState::class);
        $appStateProph->getMiddlewareConfig()->willReturn($config);
        $appStateProph->getLocale()->willReturn('cs_CZ');
        $appStateProph->getLocaleMap()->willReturn(['cs_CZ' => 'cs']);
        $appStateProph->isSingleLanguage()->willReturn(false);
        $appStateProph->setPage('page1')->willReturn($appStateProph)->shouldBeCalled();
        $appStateProph
            ->setRouteMap(['page1' => ['cs_CZ' => '/cs/page1-cs/{var1}']])
            ->willReturn($appStateProph)
            ->shouldBeCalled();
        $appStateProph
            ->addUrlParam('var1', $urlParam)
            ->willReturn($appStateProph)
            ->shouldBeCalled();

        if ($data !== null) {
            $appStateProph->getData()->willReturn($data);
        }

        return new RouterMiddleware($appStateProph->reveal());
    }

    /**
     * @param \Prophecy\Prophet $prophet
     * @return \Middleware\Router\RouterMiddleware
     */
    private function getMwSinglelang(Prophet $prophet = null, $config = [], $data = null, $urlParam = 'xxx')
    {
        $prophet = $prophet ? $prophet : new Prophet();
        $appStateProph = $prophet->prophesize(AppState::class);
        $appStateProph->getMiddlewareConfig()->willReturn($config);
        $appStateProph->getLocale()->willReturn('cs_CZ');
        $appStateProph->getLocaleMap()->willReturn(['cs_CZ' => '']);
        $appStateProph->isSingleLanguage()->willReturn(true);
        $appStateProph->setPage('page1')->willReturn($appStateProph)->shouldBeCalled();
        $appStateProph
            ->setRouteMap(['page1' => ['cs_CZ' => '/page1/{var1}']])
            ->willReturn($appStateProph)
            ->shouldBeCalled();
        $appStateProph
            ->addUrlParam('var1', $urlParam)
            ->willReturn($appStateProph)
            ->shouldBeCalled();

        if ($data !== null) {
            $appStateProph->getData()->willReturn($data);
        }

        return new RouterMiddleware($appStateProph->reveal());
    }
}
