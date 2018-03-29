<?php

namespace Unit\Middleware\Renderer;

use Prophecy\Argument;
use Prophecy\Prophet;
use Psr\Http\Message\ServerRequestInterface;
use TwigYard\Component\AppState;
use TwigYard\Component\TemplatingFactoryInterface;
use TwigYard\Component\TwigTemplating;
use TwigYard\Middleware\Renderer\RendererMiddleware;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;

class RendererMiddlewareCest
{
    /**
     * @param \UnitTester $I
     */
    public function noErrorOnConfigMissing(\UnitTester $I)
    {
        $prophet = new Prophet();
        $appStateProph = $prophet->prophesize(AppState::class);
        $appStateProph->getConfig()->willReturn([]);

        $mw = new RendererMiddleware(
            $appStateProph->reveal(),
            $prophet->prophesize()->willImplement(TemplatingFactoryInterface::class)->reveal()
        );
        $response = $mw(new ServerRequest(), new Response(), function () {
            return true;
        });
        $I->assertTrue($response);
    }

    /**
     * @param \UnitTester $I
     */
    public function rendersUniversalTemplateMultilang(\UnitTester $I)
    {
        $prophet = new Prophet();
        $mw = $this->getMwMultilang($prophet, $this->getUniversalTemplatingFactoryProphMultilang($prophet)->reveal());
        $callBackCalled = $mw(
            new ServerRequest(),
            new Response(),
            function (ServerRequestInterface $request, Response $response) use ($prophet, $I) {
                $prophet->checkPredictions();
                $I->assertEquals('test html', $response->getBody());

                return true;
            }
        );
        $I->assertTrue($callBackCalled);
    }

    /**
     * @param \UnitTester $I
     */
    public function rendersNoUniversalTemplateMultilang(\UnitTester $I)
    {
        $prophet = new Prophet();
        $mw = $this->getMwMultilang($prophet, $this->getNoUniversalTemplatingFactoryProphMultilang($prophet)->reveal());
        $callBackCalled = $mw(
            new ServerRequest(),
            new Response(),
            function (ServerRequestInterface $request, Response $response) use ($prophet, $I) {
                $prophet->checkPredictions();
                $I->assertEquals('test html', $response->getBody());

                return true;
            }
        );
        $I->assertTrue($callBackCalled);
    }

    /**
     * @param \UnitTester $I
     */
    public function rendersTemplateSinglelang(\UnitTester $I)
    {
        $prophet = new Prophet();
        $mw = $this->getMwSinglelang($prophet);
        $callBackCalled = $mw(
            new ServerRequest(),
            new Response(),
            function (ServerRequestInterface $request, Response $response) use ($prophet, $I) {
                $prophet->checkPredictions();
                $I->assertEquals('test html', $response->getBody());

                return true;
            }
        );
        $I->assertTrue($callBackCalled);
    }

    /**
     * @param \Prophecy\Prophet $prophet
     * @param \Component\TemplatingFactoryInterface $templatingFactory
     * @return \Middleware\Locale\LocaleMiddleware
     */
    private function getMwMultilang(Prophet $prophet = null, TemplatingFactoryInterface $templatingFactory = null)
    {
        $prophet = $prophet ? $prophet : new Prophet();
        $appStateProph = $prophet->prophesize(AppState::class);
        $appStateProph->getConfig()->willReturn(['renderer' => ['index' => 'index.html.twig']]);
        $appStateProph->getSiteDir()->willReturn('www.example.com');
        $appStateProph->getRouteMap()->willReturn([]);
        $appStateProph->getLocale()->willReturn('cs_CZ');
        $appStateProph->isSingleLanguage()->willReturn(false);
        $appStateProph->getData()->willReturn([]);
        $appStateProph->getPage()->willReturn('index');

        if ($templatingFactory === null) {
            $templatingFactory = $this->getUniversalTemplatingFactoryProphMultilang($prophet);
        }

        return new RendererMiddleware($appStateProph->reveal(), $templatingFactory);
    }

    /**
     * @param \Prophecy\Prophet $prophet
     * @return \TwigYard\Middleware\Locale\LocaleMiddleware
     */
    private function getMwSinglelang(Prophet $prophet = null)
    {
        $prophet = $prophet ? $prophet : new Prophet();
        $appStateProph = $prophet->prophesize(AppState::class);
        $appStateProph->getConfig()->willReturn(['renderer' => ['index' => 'index.html.twig']]);
        $appStateProph->getSiteDir()->willReturn('www.example.com');
        $appStateProph->getRouteMap()->willReturn([]);
        $appStateProph->isSingleLanguage()->willReturn(true);
        $appStateProph->getData()->willReturn([]);
        $appStateProph->getPage()->willReturn('index');

        return new RendererMiddleware($appStateProph->reveal(), $this->getTemplatingFactoryProphSinglelang()->reveal());
    }

    /**
     * @param \Prophecy\Prophet $prophet
     * @return \Prophecy\Prophecy\ObjectProphecy
     */
    private function getNoUniversalTemplatingFactoryProphMultilang(Prophet $prophet = null)
    {
        $prophet = $prophet ? $prophet : new Prophet();
        $templatingProph = $prophet->prophesize(TwigTemplating::class);
        $templatingProph->render('cs_CZ/index.html.twig')->willReturn('test html')->shouldBeCalled();
        $templatingProph->render('index.html.twig')->shouldNotBeCalled();
        $tplFactoryProph = $prophet->prophesize();
        $tplFactoryProph->willImplement(TemplatingFactoryInterface::class);
        $tplFactoryProph
            ->createTemplating(Argument::type(AppState::class))
            ->willReturn($templatingProph->reveal());

        return $tplFactoryProph;
    }

    /**
     * @param \Prophecy\Prophet $prophet
     * @return \Prophecy\Prophecy\ObjectProphecy
     */
    private function getUniversalTemplatingFactoryProphMultilang(Prophet $prophet = null)
    {
        $prophet = $prophet ? $prophet : new Prophet();
        $templatingProph = $prophet->prophesize(TwigTemplating::class);
        $templatingProph->render('cs_CZ/index.html.twig')->willThrow(\Twig_Error_Loader::class)->shouldBeCalled();
        $templatingProph->render('index.html.twig')->willReturn('test html')->shouldBeCalled();
        $tplFactoryProph = $prophet->prophesize();
        $tplFactoryProph->willImplement(TemplatingFactoryInterface::class);
        $tplFactoryProph
            ->createTemplating(Argument::type(AppState::class))
            ->willReturn($templatingProph->reveal());

        return $tplFactoryProph;
    }

    /**
     * @param \Prophecy\Prophet $prophet
     * @return \Prophecy\Prophecy\ObjectProphecy
     */
    private function getTemplatingFactoryProphSinglelang(Prophet $prophet = null)
    {
        $prophet = $prophet ? $prophet : new Prophet();
        $templatingProph = $prophet->prophesize(TwigTemplating::class);
        $templatingProph->render('index.html.twig')->willReturn('test html')->shouldBeCalled();
        $tplFactoryProph = $prophet->prophesize();
        $tplFactoryProph->willImplement(TemplatingFactoryInterface::class);
        $tplFactoryProph
            ->createTemplating(Argument::type(AppState::class))
            ->willReturn($templatingProph->reveal());

        return $tplFactoryProph;
    }
}
