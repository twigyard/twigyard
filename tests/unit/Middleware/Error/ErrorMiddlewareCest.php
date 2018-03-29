<?php

namespace Unit\Middleware\Error;

use Monolog\Logger;
use Prophecy\Argument\Token\AnyValuesToken;
use Prophecy\Prophet;
use TwigYard\Component\AppState;
use TwigYard\Component\LoggerFactory;
use TwigYard\Middleware\Error\ErrorMiddleware;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;

class ErrorMiddlewareCest
{
    const TEST_DIR = '/tmp/twigyard';

    /**
     * @param \UnitTester $I
     */
    public function testGlobal500(\UnitTester $I)
    {
        $this->rmTestDir();
        $mw = $this->getMw(false);
        $response = $mw(new ServerRequest(), new Response(), function () {
            throw new \Exception();
        });
        $I->assertEquals(500, $response->getStatusCode());
        $I->assertEquals(
            file_get_contents('src/TwigYard/Middleware/Error/500.html'),
            $response->getBody()->getContents()
        );
    }

    /**
     * @param \UnitTester $I
     */
    public function testGlobal404(\UnitTester $I)
    {
        $this->rmTestDir();
        $mw = $this->getMw();
        $response = $mw(new ServerRequest(), new Response(), function () {
            return (new Response())->withStatus(404);
        });
        $I->assertEquals(404, $response->getStatusCode());
        $I->assertEquals(
            file_get_contents('src/TwigYard/Middleware/Error/404.html'),
            $response->getBody()->getContents()
        );
    }

    /**
     * @param \UnitTester $I
     */
    public function testLocalizedSite404(\UnitTester $I)
    {
        $this->rmTestDir();
        $mw = $this->getMw();
        mkdir(self::TEST_DIR);
        mkdir(self::TEST_DIR . '/templates');
        mkdir(self::TEST_DIR . '/templates/xxx');
        file_put_contents(self::TEST_DIR . '/templates/xxx/404.html', 'site 404');
        $response = $mw(new ServerRequest(), new Response(), function () {
            return (new Response())->withStatus(404);
        });
        $I->assertEquals(404, $response->getStatusCode());
        $I->assertEquals('site 404', $response->getBody()->getContents());
        $this->rmTestDir();
    }

    /**
     * @param \UnitTester $I
     */
    public function testSite404(\UnitTester $I)
    {
        $this->rmTestDir();
        $mw = $this->getMw();
        mkdir(self::TEST_DIR);
        mkdir(self::TEST_DIR . '/templates');
        file_put_contents(self::TEST_DIR . '/templates/404.html', 'site 404');
        $response = $mw(new ServerRequest(), new Response(), function () {
            return (new Response())->withStatus(404);
        });
        $I->assertEquals(404, $response->getStatusCode());
        $I->assertEquals('site 404', $response->getBody()->getContents());
        $this->rmTestDir();
    }

    /**
     * @return \TwigYard\Middleware\Httpauth\HttpauthMiddleware
     */
    private function getMw($showErrors = true)
    {
        $prophet = new Prophet();
        $appStateProph = $prophet->prophesize(AppState::class);
        $appStateProph->getUrl()->willReturn('test-1');
        $appStateProph->getSiteDir()->willReturn(self::TEST_DIR);
        $appStateProph->getLocale()->willReturn('xxx');
        $appStateProph->dumpContext()->willReturn(['context' => 'xxx']);

        $loggerProph = $prophet->prophesize(Logger::class);
        $loggerProph->critical(new AnyValuesToken());
        $loggerProph->error(new AnyValuesToken());
        $loggerFactoryProph = $prophet->prophesize(LoggerFactory::class);
        $loggerFactoryProph->getLogger(new AnyValuesToken())->willReturn($loggerProph->reveal());

        return new ErrorMiddleware(
            $appStateProph->reveal(),
            $showErrors,
            $loggerFactoryProph->reveal(),
            'templates',
            '404.html',
            '500.html'
        );
    }

    /**
     * @return bool
     */
    private function rmTestDir()
    {
        $rrmDir = function ($dir) use (&$rrmDir) {
            if (!is_file($dir) && !is_dir($dir)) {
                return false;
            }

            $files = array_diff(scandir($dir), ['.', '..']);
            foreach ($files as $file) {
                (is_dir($dir . '/' . $file))
                    ? $rrmDir($dir . '/' . $file)
                    : unlink($dir . '/' . $file);
            }

            return rmdir($dir);
        };

        return $rrmDir(self::TEST_DIR);
    }
}
