<?php

namespace Unit\Middleware\Form;

use TwigYard\Component\AppState;
use TwigYard\Component\CsrfTokenGenerator;
use TwigYard\Component\TranslatorFactory;
use Dflydev\FigCookies\SetCookies;
use TwigYard\Middleware\Form\Exception\LogDirectoryNotWritableException;
use TwigYard\Middleware\Form\Exception\InvalidFormNameException;
use TwigYard\Middleware\Form\FormHandlerFactory;
use TwigYard\Middleware\Form\FormMiddleware;
use TwigYard\Middleware\Form\FormValidator;
use TwigYard\Middleware\Form\Handler\HandlerInterface;
use TwigYard\Middleware\Form\Handler\LogHandler;
use Prophecy\Argument;
use Prophecy\Argument\Token\TypeToken;
use Prophecy\Prophet;
use Symfony\Component\Translation\Translator;
use VirtualFileSystem\FileSystem;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Uri;

class FormMiddlewareCest
{
    /**
     * @param \UnitTester $I
     */
    public function testNotWritableLogDir(\UnitTester $I)
    {
        $prophet = new Prophet();
        $appState = $prophet->prophesize(AppState::class);
        $appState->getConfig()->willReturn([
            'form' => ['form1' => ['handlers' => [['logHandlerConfig', 'type' => 'log']]]]
        ]);
        $fs = new FileSystem();
        $fs->createDirectory('/sites/www.example.com/var/log', true, 0);
        $appState->getSiteDir()->willReturn($fs->path('/sites/www.example.com'));
        $mw = $this->getMw($appState, $prophet);

        $I->expectException(LogDirectoryNotWritableException::class, function () use ($mw) {
            $mw(new ServerRequest(), new Response(), function () {
            });
        });
    }

    /**
     * @param \UnitTester $I
     */
    public function testInvalidFormName(\UnitTester $I)
    {
        $prophet = new Prophet();
        $appState = $prophet->prophesize(AppState::class);
        $appState->getConfig()->willReturn(['form' => ['invalid-form-name!' => ['handlers' =>
            [['logHandlerConfig', 'type' => 'log']],
        ]]]);
        $csrfTokenGenerator = $prophet->prophesize(CsrfTokenGenerator::class);
        $csrfTokenGenerator->generateToken()->willReturn('token');
        $formValidator = $prophet->prophesize(FormValidator::class);
        $handlerFactory =$prophet->prophesize(FormHandlerFactory::class);
        $translatorFactory = $prophet->prophesize(TranslatorFactory::class);
        $fs = new FileSystem();
        $fs->createDirectory('/sites/www.example.com/var/log', true);
        $appState->getSiteDir()->willReturn($fs->path('/sites/www.example.com'));

        $mw = new FormMiddleware(
            $appState->reveal(),
            $csrfTokenGenerator->reveal(),
            $formValidator->reveal(),
            $handlerFactory->reveal(),
            $translatorFactory->reveal()
        );

        $I->expectException(InvalidFormNameException::class, function () use ($mw) {
            $mw(new ServerRequest(), new Response(), function () {
                return new Response();
            });
        });
    }

    /**
     * @param \UnitTester $I
     */
    public function testValidFormName(\UnitTester $I)
    {
        $prophet = new Prophet();
        $appState = $prophet->prophesize(AppState::class);
        $appState->getConfig()->willReturn(['form' => ['valid_form_n4m3' => ['handlers' =>
            [['logHandlerConfig', 'type' => 'log']],
        ]]]);
        $appState->setForm(['valid_form_n4m3' => [
            'data' => ['csrf_token' => 'token'],
        ]])->shouldBeCalled();
        $fs = new FileSystem();
        $fs->createDirectory('/sites/www.example.com/var/log', true);
        $appState->getSiteDir()->willReturn($fs->path('/sites/www.example.com'));
        $csrfTokenGenerator = $prophet->prophesize(CsrfTokenGenerator::class);
        $csrfTokenGenerator->generateToken()->willReturn('token');
        $formValidator = $prophet->prophesize(FormValidator::class);
        $handlerFactory =$prophet->prophesize(FormHandlerFactory::class);
        $translatorFactory = $prophet->prophesize(TranslatorFactory::class);

        $mw = new FormMiddleware(
            $appState->reveal(),
            $csrfTokenGenerator->reveal(),
            $formValidator->reveal(),
            $handlerFactory->reveal(),
            $translatorFactory->reveal()
        );

        try {
            $mw(new ServerRequest(), new Response(), function () {
                return new Response();
            });
        } catch (InvalidFormNameException $ex) {
            $I->fail('Unexpected exception InvalidFormNameException');
        }
    }

    /**
     * @param \UnitTester $I
     */
    public function testPassIfConfigNotPresent(\UnitTester $I)
    {
        $prophet = new Prophet();
        $appState = $prophet->prophesize(AppState::class);
        $appState->getConfig()->willReturn([]);
        $csrfTokenGenerator = $prophet->prophesize(CsrfTokenGenerator::class);
        $formValidator = $prophet->prophesize(FormValidator::class);
        $handlerFactory = $prophet->prophesize(FormHandlerFactory::class);
        $translatorFactory = $prophet->prophesize(TranslatorFactory::class);
        $translatorFactory->getTranslator()->willReturn(new Translator('en'));

        $mw = new FormMiddleware(
            $appState->reveal(),
            $csrfTokenGenerator->reveal(),
            $formValidator->reveal(),
            $handlerFactory->reveal(),
            $translatorFactory->reveal()
        );
        $mw(new ServerRequest(), new Response(), function () {
            return new Response();
        });
        $I->assertTrue(true);
    }

    /**
     * @param \UnitTester $I
     */
    public function testPassIfFieldsMapNotPresent(\UnitTester $I)
    {
        $prophet = new Prophet();
        $appState = $prophet->prophesize(AppState::class);
        $appState->setForm(new TypeToken('array'))->shouldBeCalled();
        $appState->getSiteParameters()->willReturn([]);
        $appState->getConfig()->willReturn([
            'form' => [
                'form1' => [
                    'success_flash_message' => 'flash_message',
                    'handlers' => [
                        ['formHandler', 'type' => 'handlerType'],
                    ],
                ],
            ],
        ]);
        $csrfTokenGenerator = $prophet->prophesize(CsrfTokenGenerator::class);
        $formValidator = $prophet->prophesize(FormValidator::class);
        $formValidator
            ->validate([], new TypeToken('array'), new TypeToken('string'), new TypeToken(Translator::class))
            ->shouldBeCalled();
        $formValidator->getFlashMessage()->willReturn('flash_message')->shouldBeCalled();
        $formValidator->getErrors()->willReturn([])->shouldBeCalled();
        $handlerFactory = $prophet->prophesize(FormHandlerFactory::class);
        $translatorFactory = $prophet->prophesize(TranslatorFactory::class);
        $translatorFactory->getTranslator()->willReturn(new Translator('en'));

        $mw = new FormMiddleware(
            $appState->reveal(),
            $csrfTokenGenerator->reveal(),
            $formValidator->reveal(),
            $handlerFactory->reveal(),
            $translatorFactory->reveal()
        );
        $mw($this->getRequest()->withCookieParams(['twigyard_csrf_token' => 'token']), new Response(), function () {
            return new Response();
        });
        $I->assertTrue(true);
    }

    /**
     * @param \UnitTester $I
     */
    public function setCookieAndCsrfOnGetRequest(\UnitTester $I)
    {
        $prophet = new Prophet();
        $appState = $prophet->prophesize(AppState::class);
        $appState->setForm(['form1' => [
            'data' => ['csrf_token' => 'token'],
            'flash_message' => 'Flash message',
        ]])->shouldBeCalled();
        $fs = $this->getFs();
        $appState->getSiteDir()->willReturn($fs->path('/sites/www.example.com'));
        $mw = $this->getMw($appState, $prophet);
        $request = (new ServerRequest())->withCookieParams(['twigyard_flash_message' => 'Flash message']);
        $response = $mw($request, new Response(), function () {
            return new Response();
        });
        $prophet->checkPredictions();
        $flashMessage = SetCookies::fromResponse($response)->get('twigyard_flash_message')->getValue();
        $csrfToken = SetCookies::fromResponse($response)->get('twigyard_csrf_token')->getValue();
        $I->assertEquals('token', $csrfToken);
        $I->assertEquals(null, $flashMessage);
    }

    /**
     * @param \UnitTester $I
     */
    public function validatePostRequestValidDataNoAnchorNoSuccessFlash(\UnitTester $I)
    {
        $this->validateWithValidData($I);
    }

    /**
     * @param \UnitTester $I
     */
    public function validatePostRequestValidDataAnchorAndFlashSuccess(\UnitTester $I)
    {
        $this->validateWithValidData($I, 'anchor-name', 'custom success flash message');
    }

    /**
     * @param \UnitTester $I
     */
    public function validatePostRequestInvalidData(\UnitTester $I)
    {
        $prophet = new Prophet();
        $appState = $this->getAppState($prophet);
        $appState->setForm(['form1' => [
            'data' => ['csrf_token' => 'token', 'field1' => 'value1'],
            'flash_message' => 'Flash message',
            'errors' => [],
        ]])->shouldBeCalled();
        $fs = $this->getFs();
        $appState->getSiteDir()->willReturn($fs->path('/sites/www.example.com'));

        $formValidator = $prophet->prophesize(FormValidator::class);
        $formValidator
            ->validate(
                [],
                ['csrf_token' => 'token', 'field1' => 'value1'],
                'invalid',
                Argument::type(Translator::class)
            )
            ->willReturn(false);
        $formValidator->getErrors()->willReturn([]);
        $formValidator->getFlashMessage()->willReturn('Flash message');

        $mw = $this->getMw($appState, $prophet, $formValidator, $this->getHandlerFactory($prophet));
        $request = $this->getRequest()->withCookieParams(['twigyard_csrf_token' => 'invalid']);
        $response = $mw($request, new Response(), function () {
            return new Response();
        });
        $csrfToken = SetCookies::fromResponse($response)->get('twigyard_csrf_token')->getValue();
        $I->assertEquals('token', $csrfToken);
    }

    /**
     * @param \UnitTester $I
     */
    public function refreshCsrfToken(\UnitTester $I)
    {
        $prophet = new Prophet();
        $appState = $this->getAppState($prophet);
        $appState->setForm(['form1' => [
            'data' => ['csrf_token' => 'token', 'field1' => 'value1'],
        ]])->shouldBeCalled();

        $fs = $this->getFs();
        $appState->getSiteDir()->willReturn($fs->path('/sites/www.example.com'));

        $formValidator = $prophet->prophesize(FormValidator::class);
        $formValidator
            ->validate(
                [],
                ['csrf_token' => 'old_token', 'field1' => 'value1'],
                'old_token',
                Argument::type(Translator::class)
            )
            ->willReturn(true);

        $mw = $this->getMw($appState, $prophet, $formValidator, $this->getHandlerFactory($prophet, 'old_token'));
        $request = $this->getRequest()
            ->withCookieParams(['twigyard_csrf_token' => 'old_token'])
            ->withMethod('post')
            ->withParsedBody(['form1' => ['csrf_token' => 'old_token', 'field1' => 'value1']]);
        $response = $mw($request, new Response(), function () {
            return null;
        });
        $I->assertNotNull($response);
        $I->assertEquals(302, $response->getStatusCode());
    }

    /**
     * @param \Prophecy\Prophecy\ObjectProphecy $appState
     * @param \Prophecy\Prophet $prophet
     * @param \Prophecy\Prophecy\ObjectProphecy $formValidator
     * @param \Prophecy\Prophecy\ObjectProphecy $handlerFactory
     * @param array $confParams
     * @return FormMiddleware
     */
    private function getMw(
        $appState,
        $prophet,
        $formValidator = null,
        $handlerFactory = null,
        array $confParams = null
    ) {
        $config = ['form' => [
            'form1' => [
                'handlers' => [['emailHandlerConfig', 'type' => 'email'], ['logHandlerConfig', 'type' => 'log']],
                'fields' => [],
            ],
        ]];
        if (!empty($confParams['anchor'])) {
            $config['form']['form1']['anchor'] = $confParams['anchor'];
        }
        if (!empty($confParams['success_flash_message'])) {
            $config['form']['form1']['success_flash_message'] = $confParams['success_flash_message'];
        }

        $appState->getConfig()->willReturn($config);
        $csrfTokenGenerator = $prophet->prophesize(CsrfTokenGenerator::class);
        $csrfTokenGenerator->generateToken()->willReturn('token');
        $formValidator = $formValidator ? $formValidator : $prophet->prophesize(FormValidator::class);
        $handlerFactory = $handlerFactory ? $handlerFactory : $prophet->prophesize(FormHandlerFactory::class);
        $translatorFactory = $prophet->prophesize(TranslatorFactory::class);
        $translatorFactory->getTranslator()->willReturn(new Translator('en'));

        return new FormMiddleware(
            $appState->reveal(),
            $csrfTokenGenerator->reveal(),
            $formValidator->reveal(),
            $handlerFactory->reveal(),
            $translatorFactory->reveal()
        );
    }

    /**
     * @param \Prophecy\Prophet $prophet
     * @param string $token
     * @return \Prophecy\Prophecy\ObjectProphecy
     */
    private function getHandlerFactory(Prophet $prophet, $token = 'token')
    {
        $emailHandler = $prophet->prophesize(HandlerInterface::class);
        $emailHandler->handle(['csrf_token' => $token, 'field1' => 'value1'])->shouldBeCalled();

        $logHandler = $prophet->prophesize(LogHandler::class);
        $logHandler->handle(new TypeToken('array'))->shouldBeCalled();

        $handlerFactory = $prophet->prophesize(FormHandlerFactory::class);
        $handlerFactory->build(['emailHandlerConfig', 'type' => 'email'], [])->willReturn($emailHandler->reveal());
        $handlerFactory->build(['logHandlerConfig', 'type' => 'log'], [])->willReturn($logHandler->reveal());

        return $handlerFactory;
    }

    /**
     * @param \Prophecy\Prophet $prophet
     * @return \Component\AppState
     */
    private function getAppState(Prophet $prophet)
    {
        $appState = $prophet->prophesize(AppState::class);
        $appState->getForm()->willReturn(['form1' => ['data' => ['csrf_token' => 'token']]]);
        $appState->getSiteParameters()->willReturn([]);

        return $appState;
    }

    /**
     * @return \Psr\Http\Message\RequestInterface
     */
    private function getRequest()
    {
        return (new ServerRequest(['REMOTE_ADDR' => '127.0.0.1']))
            ->withMethod('pOsT') // Ensure method name case gets converted
            ->withParsedBody(['form1' => ['csrf_token' => 'token', 'field1' => 'value1']])
            ->withUri((new Uri())->withPath('/form/page'));
    }

    /**
     * @param \UnitTester $I
     * @param string $anchor
     * @param string $successFlashMessage
     */
    private function validateWithValidData(\UnitTester $I, $anchor = null, $successFlashMessage = null)
    {
        $prophet = new Prophet();
        $appState = $this->getAppState($prophet);

        $formAppState = ['form1' => ['data' => ['csrf_token' => 'token', 'field1' => 'value1']]];
        if ($anchor) {
            $formAppState['form1']['anchor'] = $anchor;
        }
        $appState->setForm($formAppState)->shouldBeCalled();

        $fs = $this->getFs();
        $appState->getSiteDir()->willReturn($fs->path('/sites/www.example.com'));

        $formValidator = $prophet->prophesize(FormValidator::class);
        $formValidator
            ->validate(
                [],
                ['csrf_token' => 'token', 'field1' => 'value1'],
                'token',
                Argument::type(Translator::class)
            )
            ->willReturn(true);

        $config = [];
        if ($anchor) {
            $config['anchor'] = $anchor;
        }
        if ($successFlashMessage) {
            $config['success_flash_message'] = $successFlashMessage;
        }

        $mw = $this->getMw($appState, $prophet, $formValidator, $this->getHandlerFactory($prophet), $config);
        $request = $this->getRequest()->withCookieParams(['twigyard_csrf_token' => 'token']);

        $response = $mw($request, new Response(), function () {
            return new Response();
        });

        $prophet->checkPredictions();
        $flashMessage = SetCookies::fromResponse($response)->get('twigyard_flash_message')->getValue();
        $I->assertEquals(
            $successFlashMessage ? $successFlashMessage : 'The form was successfully sent, thank you!',
            $flashMessage
        );
        $I->assertEquals(302, $response->getStatusCode());
        $I->assertEquals(
            '/form/page?formSent=form1' . ($anchor ? '#' . $anchor : ''),
            $response->getHeaderLine('Location')
        );
    }

    /**
     * @return FileSystem
     */
    private function getFs()
    {
        $fs = new FileSystem();
        $fs->createDirectory('/sites/www.example.com/var/log', true);
        return $fs;
    }
}
