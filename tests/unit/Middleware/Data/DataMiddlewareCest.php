<?php

namespace Unit\Middleware\Data;

use TwigYard\Component\AppState;
use TwigYard\Exception\InvalidSiteConfigException;
use TwigYard\Middleware\Data\DataMiddleware;
use Prophecy\Prophet;
use VirtualFileSystem\FileSystem;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;

class DataMiddlewareCest
{
    /**
     * @param \UnitTester $I
     */
    public function parsesDataYamlIfDefined(\UnitTester $I)
    {
        $prophet = new Prophet();
        $fs = $this->getFs();
        $mw = $this->getMw($fs, $prophet);
        $callBackCalled = $mw(new ServerRequest(), new Response(), function () use ($prophet) {
            $prophet->checkPredictions();
            return true;
        });
        $I->assertTrue($callBackCalled);
    }

    /**
     * @param \UnitTester $I
     */
    public function exceptionOnMissingDataYml(\UnitTester $I)
    {
        $I->seeExceptionThrown(InvalidSiteConfigException::class, function () {
            $fs = new FileSystem();
            $mw = $this->getMw($fs);
            $mw(new ServerRequest(), new Response(), function () {
            });
        });
    }

    /**
     * @param \UnitTester $I
     */
    public function exceptionOnInvalidDataYml(\UnitTester $I)
    {
        $I->seeExceptionThrown(InvalidSiteConfigException::class, function () {
            $fs = new FileSystem();
            $fs->createDirectory('/sites/www.example.com/data', true);
            file_put_contents($fs->path('/sites/www.example.com/data/dataSet.yml'), '  Invalid yml content');
            $mw = $this->getMw($fs);
            $mw(new ServerRequest(), new Response(), function () {
            });
        });
    }

    /**
     * @param \UnitTester $I
     */
    public function noErrorOnConfigMissing(\UnitTester $I)
    {
        $prophet =  new Prophet();
        $appStateProph = $prophet->prophesize(AppState::class);
        $appStateProph->getConfig()->willReturn([]);

        $mw = new DataMiddleware($appStateProph->reveal(), 'data');
        $response = $mw(new ServerRequest(), new Response(), function () {
            return true;
        });
        $I->assertTrue($response);
    }

    /**
     * @return \VirtualFileSystem\FileSystem
     */
    private function getFs()
    {
        $fs = new FileSystem();
        $fs->createDirectory('/sites/www.example.com/data', true);
        file_put_contents($fs->path('/sites/www.example.com/data/dataSet.yml'), 'var: value');

        return $fs;
    }

    /**
     * @param \VirtualFileSystem\FileSystem $fs
     * @param \Prophecy\Prophet|null $prophet
     * @return \TwigYard\Middleware\Data\DataMiddleware
     */
    private function getMw(FileSystem $fs, Prophet $prophet = null)
    {
        $prophet =  $prophet ? $prophet : new Prophet();
        $appStateProph = $prophet->prophesize(AppState::class);
        $appStateProph->getConfig()->willReturn(['data' => ['dataSet' => 'dataSet.yml']]);
        $appStateProph->getSiteDir()->willReturn($fs->path('/sites/www.example.com'));
        $appStateProph->setData(['dataSet' => ['var' => 'value']])->shouldBeCalled();

        return new DataMiddleware($appStateProph->reveal(), 'data');
    }
}
