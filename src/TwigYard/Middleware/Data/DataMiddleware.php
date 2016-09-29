<?php

namespace TwigYard\Middleware\Data;

use TwigYard\Component\AppState;
use TwigYard\Exception\InvalidSiteConfigException;
use TwigYard\Middleware\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class DataMiddleware implements MiddlewareInterface
{
    /**
     * @var string
     */
    private $dataDir;
    /**
     * @var \TwigYard\Component\AppState
     */
    private $appState;

    /**
     * @param \TwigYard\Component\AppState $appState
     * @param string $dataDir
     */
    public function __construct(AppState $appState, $dataDir)
    {
        $this->dataDir = $dataDir;
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
        if (array_key_exists('data', $this->appState->getConfig())) {
            $conf = $this->appState->getConfig()['data'];
            $data = [];
            foreach ($conf as $var => $file) {
                $dataFile = $this->appState->getSiteDir() . '/' . $this->dataDir . '/' . $file;
                if (!is_file($dataFile)) {
                    throw new InvalidSiteConfigException(sprintf('Missing data file: %s', $dataFile));
                }
                try {
                    $data[$var] = Yaml::parse(file_get_contents($dataFile));
                } catch (ParseException $e) {
                    throw new InvalidSiteConfigException(sprintf('Invalid data.yml with message %s', $e->getMessage()));
                }
            }
            $this->appState->setData($data);
        }

        return $next($request, $response);
    }
}
