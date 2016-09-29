<?php

namespace TwigYard\Middleware\Router;

use TwigYard\Component\AppState;
use TwigYard\Middleware\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;

class RouterMiddleware implements MiddlewareInterface
{
    /**
     * @var \TwigYard\Component\AppState
     */
    private $appState;

    /**
     * @param \TwigYard\Component\AppState $appState
     */
    public function __construct(AppState $appState)
    {
        $this->appState = $appState;
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param callable|\TwigYard\Middleware\MiddlewareInterface $next
     * @return \Psr\Http\Message\ResponseInterface $response
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        if (array_key_exists('router', $this->appState->getConfig())) {
            $conf = $this->appState->getConfig()['router'];
            $activePage = $this->getActivePage($request, $conf);
            if (!$activePage) {
                return (new Response())->withStatus(404);
            }

            $this->appState->setRouteMap($this->getRouteMap($conf));
            $this->appState->setPage($activePage);
        }

        return $next($request, $response);
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param array $conf
     * @return null|string
     */
    private function getActivePage(ServerRequestInterface $request, array $conf)
    {
        foreach ($conf as $pageName => $confUris) {
            $reqPathArr = explode('/', $request->getUri()->getPath());
            $confUri = $this->appState->isSingleLanguage() ? $confUris : $confUris[$this->appState->getLocale()];
            $confPathArr = explode('/', $confUri);

            foreach ($confPathArr as $key => $confPathSegment) {
                $matches = [];

                if (preg_match('/\{\s*(.+?)\s*(\|\s*(.+?):(.+))?\s*\}/', $confPathSegment, $matches)) {
                    $paramFound = false;
                    if (isset($matches[3])) {
                        if (!array_key_exists($matches[3], $this->appState->getData())) {
                            return null;
                        }

                        $propArr = explode('.', $matches[4]);
                        foreach ($this->appState->getData()[$matches[3]] as $param) {
                            foreach ($propArr as $propPart) {
                                if (!isset($param[$propPart])) {
                                    return (new Response())->withStatus(404);
                                }
                                $param = $param[$propPart];
                            }
                            if (isset($reqPathArr[$key]) && (string) $param === $reqPathArr[$key]) {
                                $paramFound = true;
                                break;
                            }
                        }

                        if (!$paramFound) {
                            break;
                        }
                    }

                    if (isset($reqPathArr[$key])) {
                        $this->appState->addUrlParam($matches[1], $reqPathArr[$key]);
                        $confPathArr[$key] = $reqPathArr[$key];
                    }
                }
            }

            if ($confPathArr === $reqPathArr) {
                return $pageName;
            }
        }

        return null;
    }

    /**
     * @param array $conf
     * @return array
     */
    private function getRouteMap(array $conf)
    {
        $routeMap = [];
        foreach ($conf as $pageName => $confUris) {
            if (!$this->appState->isSingleLanguage()) {
                foreach ($confUris as $locale => $confUri) {
                    $localeCode = $this->appState->getLocaleMap()[$locale];
                    $routeMap[$pageName][$locale] = '/' . $localeCode . preg_replace('/\s*\|\s*[^}]*/', '', $confUri);
                }
                continue;
            }

            $routeMap[$pageName][$this->appState->getLocale()] = preg_replace('/\s*\|\s*[^}]*/', '', $confUris);
        }

        return $routeMap;
    }
}
