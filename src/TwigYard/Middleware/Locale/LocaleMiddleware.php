<?php

namespace TwigYard\Middleware\Locale;

use TwigYard\Component\AppState;
use TwigYard\Exception\InvalidSiteConfigException;
use TwigYard\Middleware\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\RedirectResponse;

class LocaleMiddleware implements MiddlewareInterface
{
    /**
     * @var array
     */
    private $validLocales;

    /**
     * @var \TwigYard\Component\AppState
     */
    private $appState;

    /**
     * @param \TwigYard\Component\AppState $appState
     * @param array $validLocales
     */
    public function __construct(AppState $appState, array $validLocales)
    {
        $this->validLocales = $validLocales;
        $this->appState = $appState;
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param callable|\TwigYard\Middleware\MiddlewareInterface $next
     * @return \Psr\Http\Message\ResponseInterface $response
     * @throws \TwigYard\Exception\InvalidSiteConfigException
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        if (array_key_exists('locale', $this->appState->getConfig())) {
            $conf = $this->appState->getConfig()['locale'];
            if (is_array($conf)) {
                if (!in_array($conf['default']['name'], $this->validLocales)) {
                    throw $this->getInvalidLocaleException();
                }
                $localeMap = [$conf['default']['name'] => $conf['default']['key']];
                foreach ($conf['extra'] as $key => $name) {
                    if (!in_array($name, $this->validLocales)) {
                        throw $this->getInvalidLocaleException();
                    }
                    $localeMap[$name] = $key;
                }

                $pathArr = explode('/', $request->getUri()->getPath());
                array_shift($pathArr);
                $langCode = array_shift($pathArr);
                $newPath = '/' . implode('/', $pathArr);
                if (!in_array($langCode, $localeMap)) {
                    return new RedirectResponse('/' . $conf['default']['key'] . $request->getUri()->getPath(), 302);
                }
                $this->appState->setLocale(array_flip($localeMap)[$langCode]);
                $this->appState->setLocaleMap($localeMap);
                $request = $request->withUri($request->getUri()->withPath($newPath));
            } else {
                if (!in_array($conf, $this->validLocales)) {
                    throw $this->getInvalidLocaleException();
                }
                $this->appState->setLocale($conf);
                $this->appState->setLocaleMap([$conf => '']);
            }
        }

        return $next($request, $response);
    }

    /**
     * @return \TwigYard\Exception\InvalidSiteConfigException
     */
    private function getInvalidLocaleException()
    {
        return new InvalidSiteConfigException(
            sprintf('The specified locale is invalid. The allowed locales are: %s', implode(' ,', $this->validLocales))
        );
    }
}
