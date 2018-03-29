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
     * @param AppState $appState
     * @param bool $showErrors
     * @param LoggerFactory $loggerFactory
     * @param string $templateDir
     * @param string $page404
     * @param string $page500
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
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable $next
     * @throws \Exception
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        if ($this->showErrors !== true) {
            set_error_handler(function ($errNo, $errStr) use ($request) {
                if (!ini_get('error_reporting')) {
                    return;
                }
                $this->loggerFactory->getLogger($this->appState->getUrl())
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
            $this->loggerFactory->getLogger($this->appState->getUrl())
                ->critical('[' . $request->getUri() . '] > ' . $e, $this->appState->dumpContext());
            $errStream = $this->getErrorPageStream($this->page500);

            return (new Response())
                ->withBody($errStream)
                ->withStatus(500);
        }

        if ($response->getStatusCode() === 404) {
            $this->loggerFactory->getLogger($this->appState->getUrl())
                ->info('[' . $request->getUri() . '] > 404', $this->appState->dumpContext());
            $errStream = $this->getErrorPageStream($this->page404);
            $response = $response->withBody($errStream);
        }

        return $response;
    }

    /**
     * @param string $pageName
     * @return Stream
     */
    private function getErrorPageStream(string $pageName): Stream
    {
        $errPage = $this->appState->getSiteDir() . '/' . $this->templateDir . '/' . $pageName;
        $localeErrPage = $this->appState->getSiteDir()
            . '/' . $this->templateDir
            . '/' . $this->appState->getLocale()
            . '/' . $pageName;

        if (is_file($localeErrPage)) {
            return  new Stream($localeErrPage);
        } elseif (is_file($errPage)) {
            return  new Stream($errPage);
        }

        return new Stream(__DIR__ . '/' . $pageName);
    }
}
