<?php

namespace TwigYard\Middleware\Form\Handler;

use TwigYard\Component\AppState;
use Monolog\Logger;

class LogHandler implements HandlerInterface
{
    /**
     * @var array
     */
    private $config;

    /**
     * @var AppState
     */
    private $appState;

    /**
     * @var \Monolog\Logger
     */
    private $logger;

    /**
     * EmailHandler constructor.
     * @param array $config
     * @param \Monolog\Logger $logger
     * @param AppState $appState
     */
    public function __construct(
        array $config,
        Logger $logger,
        AppState $appState
    ) {
        $this->config = $config;
        $this->logger = $logger;
        $this->appState = $appState;
    }

    /**
     * @param array $formData
     */
    public function handle(array $formData)
    {
        unset($formData['csrf_token']);
        $this->logger->addInfo('IP: ' . $this->appState->getRemoteIp() . ', FORM_DATA:', $formData);
    }
}
