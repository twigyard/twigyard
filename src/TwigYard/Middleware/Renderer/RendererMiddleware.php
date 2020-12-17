<?php

namespace TwigYard\Middleware\Renderer;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TwigYard\Component\AppState;
use TwigYard\Component\TemplatingFactoryInterface;
use TwigYard\Exception\MissingAppStateAttributeException;
use TwigYard\Middleware\MiddlewareInterface;

class RendererMiddleware implements MiddlewareInterface
{
    /**
     * @var TemplatingFactoryInterface
     */
    private $templatingFactory;

    /**
     * @var AppState
     */
    private $appState;

    /**
     * RendererMiddleware constructor.
     */
    public function __construct(AppState $appState, TemplatingFactoryInterface $templatingFactory)
    {
        $this->templatingFactory = $templatingFactory;
        $this->appState = $appState;
    }

    /**
     * @throws MissingAppStateAttributeException
     * @throws \Twig_Error_Loader
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        if (array_key_exists('renderer', $this->appState->getMiddlewareConfig())) {
            $conf = $this->appState->getMiddlewareConfig()['renderer'];
            $templating = $this->templatingFactory->createTemplating($this->appState);
            $localeSubDir = $this->appState->isSingleLanguage() ? '' : $this->appState->getLocale() . '/';

            try {
                $html = $templating->render($localeSubDir . $conf[$this->appState->getPage()]);
            } catch (\Twig_Error_Loader $ex) {
                if ($localeSubDir === '') {
                    throw $ex;
                }
                $html = $templating->render($conf[$this->appState->getPage()]);
            }

            $response->getBody()->write($html);
        }

        return $next($request, $response);
    }
}
