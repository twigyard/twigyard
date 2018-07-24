<?php

namespace TwigYard\Middleware\Form;

use TwigYard\Component\AppState;
use TwigYard\Component\HttpRequestSender;
use TwigYard\Component\MailerFactory;
use TwigYard\Component\SiteLoggerFactory;
use TwigYard\Component\TemplatingFactoryInterface;
use TwigYard\Exception\InvalidSiteConfigException;
use TwigYard\Middleware\Form\Exception\InvalidFormHandlerException;
use TwigYard\Middleware\Form\Handler\ApiHandler;
use TwigYard\Middleware\Form\Handler\EmailHandler;
use TwigYard\Middleware\Form\Handler\HandlerInterface;
use TwigYard\Middleware\Form\Handler\LogHandler;

class FormHandlerFactory
{
    const TYPE_API = 'api';
    const TYPE_EMAIL = 'email';
    const TYPE_LOG = 'log';

    /**
     * @var MailerFactory
     */
    private $mailerFactory;

    /**
     * @var AppState
     */
    private $appState;

    /**
     * @var TemplatingFactoryInterface
     */
    private $templatingFactory;

    /**
     * @var SiteLoggerFactory
     */
    private $siteLoggerFactory;

    /**
     * @var HttpRequestSender
     */
    private $httpRequestSender;

    /**
     * @param AppState $appState
     * @param MailerFactory $mailerFactory
     * @param TemplatingFactoryInterface $templatingFactory
     * @param SiteLoggerFactory $siteLoggerFactory
     * @param HttpRequestSender $httpRequestSender
     */
    public function __construct(
        AppState $appState,
        MailerFactory $mailerFactory,
        TemplatingFactoryInterface $templatingFactory,
        SiteLoggerFactory $siteLoggerFactory,
        HttpRequestSender $httpRequestSender
    ) {
        $this->mailerFactory = $mailerFactory;
        $this->appState = $appState;
        $this->templatingFactory = $templatingFactory;
        $this->siteLoggerFactory = $siteLoggerFactory;
        $this->httpRequestSender = $httpRequestSender;
    }

    /**
     * @param array $config
     * @throws InvalidFormHandlerException
     * @throws InvalidSiteConfigException
     * @return HandlerInterface
     */
    public function build(array $config): HandlerInterface
    {
        if ($config['type'] === self::TYPE_API) {
            return new ApiHandler(
                $config,
                $this->httpRequestSender
            );
        } elseif ($config['type'] === self::TYPE_EMAIL) {
            return new EmailHandler(
                $config,
                $this->mailerFactory->createMailer($this->appState->getComponentConfig()['mailer']),
                $this->templatingFactory,
                $this->appState
            );
        } elseif ($config['type'] === self::TYPE_LOG) {
            return new LogHandler(
                $this->siteLoggerFactory->getFormLogger($this->appState->getSiteDir(), $config['file']),
                $this->appState
            );
        }

        throw new InvalidFormHandlerException();
    }
}
