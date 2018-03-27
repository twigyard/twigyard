<?php

namespace TwigYard\Middleware\Form\Handler;

use TwigYard\Component\AppState;
use TwigYard\Component\Mailer;
use TwigYard\Component\TemplatingFactoryInterface;
use TwigYard\Component\TemplatingInterface;
use TwigYard\Exception\MissingAppStateAttributeException;

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
     * @var TemplatingInterface|null
     */
    private $templating;

    /**
     * @var string|null
     */
    private $localeSubDir;

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
        $this->templating = null;
        $this->localeSubDir = null;
    }

    /**
     * @param array $formData
     * @throws MissingAppStateAttributeException
     */
    public function handle(array $formData): void
    {
        $subjectContent = $this->renderTemplate($this->config['templates']['subject']);
        $bodyContent = $this->renderTemplate($this->config['templates']['body']);

        $messageBuilder = $this->mailer->getMessageBuilder();
        $messageBuilder
            ->setFrom([$this->config['from']['address'] => $this->config['from']['name']])
            ->setSubject($subjectContent)
            ->setBody($bodyContent);
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
    public function replacePlaceholders(array $valueArr, array $formData): array
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

    /**
     * @param string $name
     * @throws MissingAppStateAttributeException
     * @return string
     */
    private function renderTemplate(string $name): string
    {
        if (!$this->templating) {
            $this->templating = $this->templatingFactory->createTemplating($this->appState);
        }

        if (!$this->localeSubDir) {
            $this->localeSubDir = $this->appState->isSingleLanguage() ? '' : $this->appState->getLocale() . '/';
        }

        try {
            return $this->templating->render($this->localeSubDir . $name);
        } catch (\Twig_Error_Loader $e) {
            return $this->templating->render($name);
        }
    }
}
