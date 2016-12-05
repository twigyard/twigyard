<?php

namespace Unit\Middleware\Locale;

use TwigYard\Component\AppState;
use TwigYard\Exception\InvalidSiteConfigException;
use TwigYard\Middleware\Locale\LocaleMiddleware;
use Prophecy\Argument;
use Prophecy\Prophet;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Uri;

class LocaleMiddlewareCest
{
    /**
     * @param \UnitTester $I
     */
    public function setLocaleIfSingleLang(\UnitTester $I)
    {
        $prophet = new Prophet();
        $mw = $this->getMw(['locale' => 'cs_CZ'], $prophet, ['cs_CZ' => '']);
        $callBackCalled = $mw($this->getRequest(), new Response(), function () use ($prophet) {
            $prophet->checkPredictions();
            return true;
        });
        $I->assertTrue($callBackCalled);
    }

    /**
     * @param \UnitTester $I
     */
    public function exceptionOnInvalidSingleLangLocale(\UnitTester $I)
    {
        $I->seeExceptionThrown(InvalidSiteConfigException::class, function () {
            $mw = $this->getMw(['locale' => 'xx_XX']);
            $mw($this->getRequest(), new Response(), function () {
            });
        });
    }

    /**
     * @param \UnitTester $I
     */
    public function setLocaleAndPathIfMultiLang(\UnitTester $I)
    {
        $prophet = new Prophet();
        $request = $this->getRequest('/cs/some-page/param');
        $mw = $this->getMw($this->getMultiLangLocale(), $prophet, ['cs_CZ' => 'cs', 'en_US' => 'en'], true);
        $callBackCalled = $mw($request, new Response(), function (ServerRequestInterface $request) use ($I, $prophet) {
            $prophet->checkPredictions();
            $I->assertEquals($request->getUri()->getPath(), '/some-page/param');
            return true;
        });
        $I->assertTrue($callBackCalled);
    }

    /**
     * @param \UnitTester $I
     */
    public function exceptionOnInvalidMultiLangLocale(\UnitTester $I)
    {
        $I->seeExceptionThrown(InvalidSiteConfigException::class, function () {
            $mw = $this->getMw([
                'locale' => [
                    'default' => ['key' => 'cs', 'name' => 'cs_CZ'],
                    'extra' => ['xx' => 'xx_XX']
                ]
            ]);
            $mw($this->getRequest(), new Response(), function () {
            });
        });
    }

    /**
     * @param \UnitTester $I
     */
    public function redirectToDefaultLocale(\UnitTester $I)
    {
        $mw = $this->getMw($this->getMultiLangLocale());
        $response = $mw($this->getRequest('/test?httpauth=abc&otherQuery=123'), new Response(), function () {
        });
        $I->assertEquals($response->getHeaderLine('location'), '/cs/test');
        $I->assertEquals($response->getStatusCode(), 302);
    }

    /**
     * @param \UnitTester $I
     */
    public function noErrorOnConfigMissing(\UnitTester $I)
    {
        $prophet =  new Prophet();
        $appStateProph = $prophet->prophesize(AppState::class);
        $appStateProph->getConfig()->willReturn([]);

        $mw = new LocaleMiddleware($appStateProph->reveal(), ['cs' => 'cs_CZ']);
        $response = $mw(new ServerRequest(), new Response(), function () {
            return true;
        });
        $I->assertTrue($response);
    }

    /**
     * @param string $path
     * @return \Zend\Diactoros\ServerRequest
     */
    private function getRequest($path = '')
    {
        return (new ServerRequest())->withUri(new Uri($path));
    }

    /**
     * @return array
     */
    private function getMultiLangLocale()
    {
        return [
            'locale' => [
                'default' => ['key' => 'cs', 'name' => 'cs_CZ'],
                'extra' => ['en' => 'en_US']
            ]
        ];
    }

    /**
     * @param $config
     * @param \Prophecy\Prophet|null $prophet
     * @return \TwigYard\Middleware\Locale\LocaleMiddleware
     */
    private function getMw($config, Prophet $prophet = null, $localeMap = null, $isMultiLang = null)
    {
        $prophet = $prophet ? $prophet : new Prophet();
        $appStateProph = $prophet->prophesize(AppState::class);
        $appStateProph->getConfig()->willReturn($config);
        $appStateProph->setLocale('cs_CZ')->shouldBeCalled();
        $appStateProph->isSingleLanguage()->willReturn(false);
        if ($isMultiLang) {
            $appStateProph->setLanguageCode('cs')->shouldBeCalled();
            $appStateProph->isSingleLanguage()->willReturn(false);
        }
        $appStateProph->setLocaleMap($localeMap ? $localeMap : [])->shouldBeCalled();

        return new LocaleMiddleware($appStateProph->reveal(), ['cs' => 'cs_CZ', 'en' => 'en_US']);
    }
}
