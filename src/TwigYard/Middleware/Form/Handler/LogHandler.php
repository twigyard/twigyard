<?php

namespace TwigYard\Middleware\Form\Handler;

use Monolog\Logger;
use TwigYard\Component\AppState;

class LogHandler implements HandlerInterface
{
    /**
     * @var AppState
     */
    private $appState;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * LogHandler constructor.
     * @param Logger $logger
     * @param AppState $appState
     */
    public function __construct(
        Logger $logger,
        AppState $appState
    ) {
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
