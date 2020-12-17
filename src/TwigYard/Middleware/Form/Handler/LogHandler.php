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
     */
    public function __construct(
        Logger $logger,
        AppState $appState
    ) {
        $this->logger = $logger;
        $this->appState = $appState;
    }

    /**
     * @return void
     */
    public function handle(array $formData)
    {
        unset($formData['csrf_token']);
        $this->logger->addInfo('IP: ' . $this->appState->getRemoteIp() . ', FORM_DATA:', $formData);
    }
}
