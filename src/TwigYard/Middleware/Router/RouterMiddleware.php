<?php

namespace TwigYard\Middleware\Router;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TwigYard\Component\AppState;
use TwigYard\Exception\MissingAppStateAttributeException;
use TwigYard\Middleware\MiddlewareInterface;
use Zend\Diactoros\Response;

class RouterMiddleware implements MiddlewareInterface
{
    /**
     * @var AppState
     */
    private $appState;

    public function __construct(AppState $appState)
    {
        $this->appState = $appState;
    }

    /**
     * @throws MissingAppStateAttributeException
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        if (array_key_exists('router', $this->appState->getMiddlewareConfig())) {
            $conf = $this->appState->getMiddlewareConfig()['router'];
            $activePage = $this->getActivePage($request, $conf);
            if (!$activePage) {
                return (new Response())->withStatus(404);
            }

            $this->appState->setRouteMap($this->getRouteMap($conf));
            $this->appState->setPage((string) $activePage);
        }

        return $next($request, $response);
    }

    /**
     * @throws MissingAppStateAttributeException
     * @return int|string|null
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
                                    return null;
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
     * @throws MissingAppStateAttributeException
     */
    private function getRouteMap(array $conf): array
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
