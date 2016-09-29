<?php

namespace TwigYard\Middleware\Renderer;

use TwigYard\Component\AppState;
use TwigYard\Middleware\MiddlewareInterface;
use TwigYard\Component\TemplatingFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class RendererMiddleware implements MiddlewareInterface
{
    /**
     * @var \TwigYard\Component\TemplatingFactoryInterface
     */
    private $templatingFactory;

    /**
     * @var \TwigYard\Component\AppState
     */
    private $appState;

    /**
     * @param \TwigYard\Component\AppState $appState
     * @param \TwigYard\Component\TemplatingFactoryInterface $templatingFactory
     */
    public function __construct(AppState $appState, TemplatingFactoryInterface $templatingFactory)
    {
        $this->templatingFactory = $templatingFactory;
        $this->appState = $appState;
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param callable|\TwigYard\Middleware\MiddlewareInterface $next
     * @return \Psr\Http\Message\ResponseInterface $response
     * @throws \TwigYard\Exception\InvalidSiteConfigException
     * @throws \Twig_Error_Loader
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        if (array_key_exists('renderer', $this->appState->getConfig())) {
            $conf = $this->appState->getConfig()['renderer'];
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
