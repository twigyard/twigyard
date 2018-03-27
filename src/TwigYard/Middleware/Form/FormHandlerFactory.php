<?php

namespace TwigYard\Middleware\Form;

use TwigYard\Component\AppState;
use TwigYard\Component\MailerFactory;
use TwigYard\Component\SiteLoggerFactory;
use TwigYard\Component\TemplatingFactoryInterface;
use TwigYard\Middleware\Form\Exception\InvalidFormHandlerException;
use TwigYard\Middleware\Form\Handler\EmailHandler;
use TwigYard\Middleware\Form\Handler\LogHandler;

class FormHandlerFactory
{
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
     * @param AppState $appState
     * @param MailerFactory $mailerFactory
     * @param TemplatingFactoryInterface $templatingFactory
     * @param SiteLoggerFactory $siteLoggerFactory
     */
    public function __construct(
        AppState $appState,
        MailerFactory $mailerFactory,
        TemplatingFactoryInterface $templatingFactory,
        SiteLoggerFactory $siteLoggerFactory
    ) {
        $this->mailerFactory = $mailerFactory;
        $this->appState = $appState;
        $this->templatingFactory = $templatingFactory;
        $this->siteLoggerFactory = $siteLoggerFactory;
    }

    /**
     * @param array $config
     * @param array $siteParameters
     * @throws \TwigYard\Middleware\Form\Exception\InvalidFormHandlerException
     * @return \TwigYard\Middleware\Form\Handler\HandlerInterface
     */
    public function build(array $config, array $siteParameters)
    {
        if ($config['type'] === self::TYPE_EMAIL) {
            return new EmailHandler(
                $config,
                $this->mailerFactory->createMailer($siteParameters),
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
