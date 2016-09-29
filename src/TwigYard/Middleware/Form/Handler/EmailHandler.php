<?php

namespace TwigYard\Middleware\Form\Handler;

use TwigYard\Component\AppState;
use TwigYard\Component\Mailer;
use TwigYard\Component\TemplatingFactoryInterface;

class EmailHandler implements HandlerInterface
{
    /**
     * @var array
     */
    private $config;

    /**
     * @var Mailer
     */
    private $mailer;

    /**
     * @var TemplatingFactoryInterface
     */
    private $templatingFactory;

    /**
     * @var AppState
     */
    private $appState;

    /**
     * EmailHandler constructor.
     * @param array $config
     * @param Mailer $mailer
     * @param TemplatingFactoryInterface $templatingFactory
     * @param AppState $appState
     */
    public function __construct(
        array $config,
        Mailer $mailer,
        TemplatingFactoryInterface $templatingFactory,
        AppState $appState
    ) {
        $this->config = $config;
        $this->mailer = $mailer;
        $this->templatingFactory = $templatingFactory;
        $this->appState = $appState;
    }

    /**
     * @param array $formData
     */
    public function handle(array $formData)
    {
        $templating = $this->templatingFactory->createTemplating($this->appState);
        $localeSubDir = $this->appState->isSingleLanguage() ? '' : $this->appState->getLocale() . '/';

        $messageBuilder = $this->mailer->getMessageBuilder();
        $messageBuilder
            ->setFrom([$this->config['from']['address'] => $this->config['from']['name']])
            ->setSubject($templating->render($localeSubDir . $this->config['templates']['subject']))
            ->setBody($templating->render($localeSubDir . $this->config['templates']['body']));
        if (isset($this->config['recipients']['to'])) {
            $addressArr = $this->replacePlaceholders($this->config['recipients']['to'], $formData);
            $messageBuilder->setTo($addressArr);
        }
        if (isset($this->config['recipients']['bcc'])) {
            $addressArr = $this->replacePlaceholders($this->config['recipients']['bcc'], $formData);
            $messageBuilder->setBcc($addressArr);
        }

        $this->mailer->send($messageBuilder);
    }

    /**
     * @param array $valueArr
     * @param array $formData
     * @return array
     */
    public function replacePlaceholders(array $valueArr, array $formData)
    {
        foreach ($valueArr as &$value) {
            $value = preg_replace_callback(
                '/{{(.+)}}/',
                function ($matches) use ($formData) {
                    return $formData[$matches[1]];
                },
                $value
            );
        }

        return $valueArr;
    }
}
