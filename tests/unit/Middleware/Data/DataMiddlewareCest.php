<?php

namespace Unit\Middleware\Data;

use TwigYard\Component\AppState;
use TwigYard\Component\CurlDownloader;
use TwigYard\Exception\CannotAccessRemoteSourceException;
use TwigYard\Exception\InvalidDataFormatException;
use TwigYard\Exception\InvalidDataTypeException;
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
        $I->seeExceptionThrown(InvalidDataFormatException::class, function () {
            $fs = new FileSystem();
            $fs->createDirectory('/sites/www.example.com/data', true);
            file_put_contents($fs->path('/sites/www.example.com/data/dataSet.yml'), '"Invalid yml content');
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
        $curlDownloader = $prophet->prophesize(CurlDownloader::class);

        $mw = new DataMiddleware($appStateProph->reveal(), 'data', $curlDownloader->reveal());
        $response = $mw(new ServerRequest(), new Response(), function () {
            return true;
        });
        $I->assertTrue($response);
    }

    /**
     * @param \UnitTester $I
     */
    public function parseJsonDataFromUrl(\UnitTester $I)
    {
        $prophet =  new Prophet();
        $appStateProph = $prophet->prophesize(AppState::class);
        $appStateProph->getConfig()->willReturn(['data' => [
            'dataSet' => [
                'type' => 'http',
                'format' => 'json',
                'resource' => 'http://url_resource.com/data.json',
            ],
        ]]);
        $appStateProph->setData(['dataSet' => ['var' => 'value']])->shouldBeCalled();

        $mw = $this->getMw(
            new FileSystem(),
            $prophet,
            $this->getCurlDownloader('http://url_resource.com/data.json', '{"var": "value"}', $prophet),
            $appStateProph->reveal()
        );
        $response = $mw(new ServerRequest(), new Response(), function () {
            return true;
        });
        $I->assertTrue($response);
    }

    /**
     * @param \UnitTester $I
     */
    public function exceptionOnMissingJsonDataFromUrl(\UnitTester $I)
    {
        $I->seeExceptionThrown(CannotAccessRemoteSourceException::class, function () {
            $prophet = new Prophet();
            $appStateProph = $prophet->prophesize(AppState::class);
            $appStateProph->getConfig()->willReturn(['data' => [
                'urlDataSet' => [
                    'type' => 'http',
                    'format' => 'json',
                    'resource' => 'http://url_resource.com/unavailable.json',
                ],
            ]]);
            $curlDownloaderProph = $prophet->prophesize(CurlDownloader::class);
            $curlDownloaderProph->loadRemoteContent('http://url_resource.com/unavailable.json')
                ->willThrow(new CannotAccessRemoteSourceException());
            $mw = $this->getMw(
                new FileSystem(),
                $prophet,
                $curlDownloaderProph->reveal(),
                $appStateProph->reveal()
            );
            $mw(new ServerRequest(), new Response(), function () {
            });
        });
    }

    public function exceptionOnInvalidTypeOfData(\UnitTester $I)
    {
        $I->seeExceptionThrown(InvalidDataTypeException::class, function () {
            $prophet = new Prophet();
            $appStateProph = $prophet->prophesize(AppState::class);
            $appStateProph->getConfig()->willReturn(['data' => [
                'urlDataSet' => [
                    'type' => 'invalid_type',
                    'format' => 'json',
                    'resource' => 'http://url_resource.com/data.json',
                ],
            ]]);
            $mw = $this->getMw(new FileSystem(), $prophet, null, $appStateProph->reveal());
            $mw(new ServerRequest(), new Response(), function () {
            });
        });
    }

    /**
     * @param \UnitTester $I
     */
    public function exceptionOnInvalidJsonDataFromUrl(\UnitTester $I)
    {
        $I->seeExceptionThrown(InvalidDataFormatException::class, function () {
            $prophet = new Prophet();
            $appStateProph = $prophet->prophesize(AppState::class);
            $appStateProph->getConfig()->willReturn(['data' => [
                'urlDataSet' => [
                    'type' => 'http',
                    'format' => 'json',
                    'resource' => 'http://url_resource.com/invalid_data.json',
                ],
            ]]);
            $mw = $this->getMw(
                new FileSystem(),
                $prophet,
                $this->getCurlDownloader('http://url_resource.com/invalid_data.json', 'invalid_json_data', $prophet),
                $appStateProph->reveal()
            );
            $mw(new ServerRequest(), new Response(), function () {
            });
        });
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
     * @param string $url
     * @param string $content
     * @param \Prophecy\Prophet|null $prophet
     * @return \TwigYard\Component\CurlDownloader
     */
    private function getCurlDownloader($url, $content, Prophet $prophet = null)
    {
        $prophet =  $prophet ?: new Prophet();
        $curlDownloaderProph = $prophet->prophesize(CurlDownloader::class);
        $curlDownloaderProph->loadRemoteContent($url)->willReturn($content)->shouldBeCalled();

        return $curlDownloaderProph->reveal();
    }

    /**
     * @param \VirtualFileSystem\FileSystem $fs
     * @param \TwigYard\Component\CurlDownloader|null $curlDownloader
     * @param \Prophecy\Prophet|null $prophet
     * @param \TwigYard\Component\AppState|null $appState
     * @return \TwigYard\Middleware\Data\DataMiddleware
     */
    private function getMw(
        FileSystem $fs,
        Prophet $prophet = null,
        CurlDownloader $curlDownloader = null,
        AppState $appState = null
    ) {
        $prophet =  $prophet ?: new Prophet();

        if ($appState === null) {
            $appStateProph = $prophet->prophesize(AppState::class);
            $appStateProph->getConfig()->willReturn(['data' => ['dataSet' => 'dataSet.yml']]);
            $appStateProph->getSiteDir()->willReturn($fs->path('/sites/www.example.com'));
            $appStateProph->setData(['dataSet' => ['var' => 'value']])->shouldBeCalled();
            $appState = $appStateProph->reveal();
        }

        if ($curlDownloader === null) {
            $curlDownloader = $prophet->prophesize(CurlDownloader::class)->reveal();
        }

        return new DataMiddleware($appState, 'data', $curlDownloader);
    }
}
