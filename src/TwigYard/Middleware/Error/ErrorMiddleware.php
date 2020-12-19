<?php

namespace TwigYard\Middleware\Error;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TwigYard\Component\AppState;
use TwigYard\Component\LoggerFactory;
use TwigYard\Middleware\MiddlewareInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\Stream;

class ErrorMiddleware implements MiddlewareInterface
{
    /**
     * @var AppState
     */
    private $appState;

    /**
     * @var bool
     */
    private $showErrors;

    /**
     * @var string
     */
    private $templateDir;

    /**
     * @var string
     */
    private $page404;

    /**
     * @var string
     */
    private $page500;

    /**
     * @var LoggerFactory
     */
    private $loggerFactory;

    /**
     * ErrorMiddleware constructor.
     */
    public function __construct(
        AppState $appState,
        bool $showErrors,
        LoggerFactory $loggerFactory,
        string $templateDir,
        string $page404,
        string $page500
    ) {
        $this->appState = $appState;
        $this->showErrors = $showErrors;
        $this->loggerFactory = $loggerFactory;
        $this->templateDir = $templateDir;
        $this->page404 = $page404;
        $this->page500 = $page500;
    }

    /**
     * @throws \Exception
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $url = null;

        try {
            $url = $this->appState->getUrl();
        } catch (\TypeError $typeError) {
        }

        if ($this->showErrors !== true) {
            set_error_handler(function ($errNo, $errStr) use ($request, $url) {
                if (!error_reporting()) {
                    return;
                }

                $this->loggerFactory->getLogger($url)
                    ->critical(
                        '[' . $request->getUri() . '] > ' . $errNo . ': ' . $errStr,
                        $this->appState->dumpContext()
                    );

                throw new \Exception($errStr);
            });
        }

        try {
            /* @var \Psr\Http\Message\ResponseInterface $response */
            $response = $next($request, $response);
        } catch (\Exception $e) {
            if ($this->showErrors === true) {
                throw $e;
            }
            $this->loggerFactory->getLogger($url)
                ->critical(
                    '[' . $request->getUri() . '] > ' . $e,
                    $this->appState->dumpContext()
                );
            $errStream = $this->getErrorPageStream($this->page500);

            return (new Response())
                ->withBody($errStream)
                ->withStatus(500);
        }

        if ($response->getStatusCode() === 404) {
            $this->loggerFactory->getLogger($url)
                ->info(
                    '[' . $request->getUri() . '] > 404',
                    $this->appState->dumpContext()
                );

            $errStream = $this->getErrorPageStream($this->page404);
            $response = $response->withBody($errStream);
        }

        return $response;
    }

    private function getErrorPageStream(string $pageName): Stream
    {
        $errPage = '';
        $localeErrPage = '';

        try {
            $errPage = $this->appState->getSiteDir() . '/' . $this->templateDir . '/' . $pageName;
            $localeErrPage = $this->appState->getSiteDir()
                . '/' . $this->templateDir
                . '/' . $this->appState->getLocale()
                . '/' . $pageName;
        } catch (\TypeError $typeError) {
        }

        if (is_file($localeErrPage)) {
            return new Stream($localeErrPage);
        }

        if (is_file($errPage)) {
            return new Stream($errPage);
        }

        return new Stream(__DIR__ . '/' . $pageName);
    }
}
