<?php

namespace Unit\Middleware\Form\Handler;

use TwigYard\Component\AppState;
use TwigYard\Component\MailerFactory;
use TwigYard\Component\MailerMessageBuilder;
use TwigYard\Component\TemplatingFactoryInterface;
use TwigYard\Component\TemplatingInterface;
use TwigYard\Middleware\Form\Handler\EmailHandler;
use Prophecy\Argument;
use Prophecy\Argument\Token\TypeToken;
use Prophecy\Prophecy\ObjectProphecy;
use Prophecy\Prophet;
use Zend\Diactoros\Response;
use TwigYard\Component\Mailer;

class EmailHandlerCest
{
    public function testWithNoBcc()
    {
        $prophet = new Prophet();
        $messageBuilder = $this->getMessageBuilder($prophet);
        $messageBuilder->setTo(['to@address'])->shouldBeCalled();

        $handler = new EmailHandler(
            [
                'from' => ['name' => 'from name', 'address' => 'from@address'],
                'recipients' => ['to' => [ 'to@address' ]],
                'templates' => ['subject' => 'subject.tpl', 'body' => 'body.tpl'],
            ],
            $this->getMailer($prophet, $messageBuilder)->reveal(),
            $this->getTemplatingFactory($prophet)->reveal(),
            $this->getAppState($prophet)->reveal()
        );
        $handler->handle([]);
        $prophet->checkPredictions();
    }

    public function testWithNoTo()
    {
        $prophet = new Prophet();
        $messageBuilder = $this->getMessageBuilder($prophet);
        $messageBuilder->setBcc(['bcc@address'])->shouldBeCalled();

        $handler = new EmailHandler(
            [
                'from' => ['name' => 'from name', 'address' => 'from@address'],
                'recipients' => ['bcc' => [ 'bcc@address' ]],
                'templates' => ['subject' => 'subject.tpl', 'body' => 'body.tpl'],
            ],
            $this->getMailer($prophet, $messageBuilder)->reveal(),
            $this->getTemplatingFactory($prophet)->reveal(),
            $this->getAppState($prophet)->reveal()
        );
        $handler->handle([]);
        $prophet->checkPredictions();
    }

    /**
     * @param \UnitTester $I
     */
    public function testWithRecipients(\UnitTester $I)
    {
        $prophet = new Prophet();
        $messageBuilder = $this->getMessageBuilder($prophet);
        $messageBuilder->setBcc(['bcc@address', 'email@email.com'])->shouldBeCalled();
        $messageBuilder->setTo(['to@address', 'email@email.com'])->shouldBeCalled();

        $handler = new EmailHandler(
            [
                'from' => ['name' => 'from name', 'address' => 'from@address'],
                'recipients' => ['to' => [ 'to@address', '{{email}}' ], 'bcc' => [ 'bcc@address', '{{email}}' ]],
                'templates' => ['subject' => 'subject.tpl', 'body' => 'body.tpl'],
            ],
            $this->getMailer($prophet, $messageBuilder)->reveal(),
            $this->getTemplatingFactory($prophet)->reveal(),
            $this->getAppState($prophet)->reveal()
        );
        $handler->handle(['email' => 'email@email.com']);
        $prophet->checkPredictions();
    }

    /**
     * @param \UnitTester $I
     */
    public function testWithNoRecipients(\UnitTester $I)
    {
        $prophet = new Prophet();
        $messageBuilder = $this->getMessageBuilder($prophet);

        $handler = new EmailHandler(
            [
                'from' => ['name' => 'from name', 'address' => 'from@address'],
                'recipients' => [],
                'templates' => ['subject' => 'subject.tpl', 'body' => 'body.tpl'],
            ],
            $this->getMailer($prophet, $messageBuilder)->reveal(),
            $this->getTemplatingFactory($prophet)->reveal(),
            $this->getAppState($prophet)->reveal()
        );
        $handler->handle([]);
        $prophet->checkPredictions();
    }

    public function testSingleLanguageSite()
    {
        $prophet = new Prophet();
        $messageBuilder = $this->getMessageBuilder($prophet);

        $handler = new EmailHandler(
            [
                'from' => ['name' => 'from name', 'address' => 'from@address'],
                'recipients' => [],
                'templates' => ['subject' => 'subject.tpl', 'body' => 'body.tpl'],
            ],
            $this->getMailer($prophet, $messageBuilder)->reveal(),
            $this->getTemplatingFactory($prophet)->reveal(),
            $this->getAppState($prophet)->reveal()
        );
        $handler->handle([]);
        $prophet->checkPredictions();
    }

    public function testMultiLanguageSite()
    {
        $prophet = new Prophet();
        $messageBuilder = $this->getMessageBuilder($prophet);

        $handler = new EmailHandler(
            [
                'from' => ['name' => 'from name', 'address' => 'from@address'],
                'recipients' => [],
                'templates' => ['subject' => 'subject.tpl', 'body' => 'body.tpl'],
            ],
            $this->getMailer($prophet, $messageBuilder)->reveal(),
            $this->getTemplatingFactory($prophet, 'cs_CZ')->reveal(),
            $this->getAppState($prophet, true)->reveal()
        );
        $handler->handle([]);
        $prophet->checkPredictions();
    }

    public function testMultiLanguageSiteGeneralTemplate()
    {
        $prophet = new Prophet();
        $messageBuilder = $this->getMessageBuilder($prophet);

        $localeSubDir = 'cs_CZ/';
        $templating = $prophet->prophesize()->willImplement(TemplatingInterface::class);
        $templating->render($localeSubDir . 'subject.tpl')->willThrow(
            new \Twig_Error_Loader(sprintf('Template "%s" is not defined.', $localeSubDir . 'subject.tpl'))
        );
        $templating->render($localeSubDir . 'body.tpl')->willThrow(
            new \Twig_Error_Loader(sprintf('Template "%s" is not defined.', $localeSubDir . 'body.tpl'))
        );
        $templating->render('subject.tpl')->willReturn('subject text');
        $templating->render('body.tpl')->willReturn('body text');

        $templatingFactory = $prophet->prophesize()->willImplement(TemplatingFactoryInterface::class);
        $templatingFactory->createTemplating(Argument::type(AppState::class))->willReturn($templating->reveal());


        $handler = new EmailHandler(
            [
                'from' => ['name' => 'from name', 'address' => 'from@address'],
                'recipients' => [],
                'templates' => ['subject' => 'subject.tpl', 'body' => 'body.tpl'],
            ],
            $this->getMailer($prophet, $messageBuilder)->reveal(),
            $templatingFactory->reveal(),
            $this->getAppState($prophet, true)->reveal()
        );
        $handler->handle([]);
        $prophet->checkPredictions();
    }

    /**
     * @param Prophet $prophet
     * @return \Prophecy\Prophecy\ObjectProphecy
     */
    private function getMessageBuilder(Prophet $prophet)
    {
        $messageBuilder = $prophet->prophesize(MailerMessageBuilder::class);
        $messageBuilder->setFrom(['from@address' => 'from name'])->shouldBeCalled()->willReturn($messageBuilder);
        $messageBuilder->setSubject('subject text')->shouldBeCalled()->willReturn($messageBuilder);
        $messageBuilder->setBody('body text')->shouldBeCalled()->willReturn($messageBuilder);

        return $messageBuilder;
    }

    /**
     * @param Prophet $prophet
     * @param \Prophecy\Prophecy\ObjectProphecy $messageBuilder
     * @return \Prophecy\Prophecy\ObjectProphecy
     */
    private function getMailer(Prophet $prophet, ObjectProphecy $messageBuilder)
    {
        $mailer = $prophet->prophesize(Mailer::class);
        $mailer->getMessageBuilder()->willReturn($messageBuilder->reveal());
        $mailer->send($messageBuilder)->shouldBeCalled();

        return $mailer;
    }
    
    /**
     * @param \Prophecy\Prophet $prophet
     * @param bool $isMultiLang
     * @return mixed
     */
    private function getAppState($prophet, $isMultiLang = false)
    {
        $appState = $prophet->prophesize(AppState::class);
        $appState->getLocale()->willReturn('cs_CZ');
        $appState->isSingleLanguage()->willReturn($isMultiLang ? false : true);

        return $appState;
    }

    /**
     * @param Prophet $prophet
     * @param string $localeSubDir
     * @return \Prophecy\Prophecy\ObjectProphecy
     */
    private function getTemplatingFactory(Prophet $prophet, $localeSubDir = null)
    {
        $localeSubDir = $localeSubDir ? $localeSubDir . '/' : '';
        $templating = $prophet->prophesize()->willImplement(TemplatingInterface::class);
        $templating->render($localeSubDir . 'subject.tpl')->willReturn('subject text');
        $templating->render($localeSubDir . 'body.tpl')->willReturn('body text');

        $templatingFactory = $prophet->prophesize()->willImplement(TemplatingFactoryInterface::class);
        $templatingFactory->createTemplating(Argument::type(AppState::class))->willReturn($templating->reveal());

        return $templatingFactory;
    }
}
