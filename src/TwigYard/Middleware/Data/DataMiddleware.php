<?php

namespace TwigYard\Middleware\Data;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use TwigYard\Component\AppState;
use TwigYard\Component\CurlDownloader;
use TwigYard\Exception\InvalidDataFormatException;
use TwigYard\Exception\InvalidDataTypeException;
use TwigYard\Exception\InvalidSiteConfigException;
use TwigYard\Middleware\MiddlewareInterface;

class DataMiddleware implements MiddlewareInterface
{
    const TYPE_LOCAL = 'local';
    const TYPE_HTTP = 'http';

    const FORMAT_YAML = 'yml';
    const FORMAT_JSON = 'json';

    /**
     * @var string
     */
    private $dataDir;

    /**
     * @var \TwigYard\Component\AppState
     */
    private $appState;

    /**
     * @var \TwigYard\Component\CurlDownloader
     */
    private $curlDownloader;

    /**
     * @param \TwigYard\Component\AppState $appState
     * @param string $dataDir
     * @param \TwigYard\Component\CurlDownloader $curlDownloader
     */
    public function __construct(AppState $appState, $dataDir, CurlDownloader $curlDownloader)
    {
        $this->dataDir = $dataDir;
        $this->appState = $appState;
        $this->curlDownloader = $curlDownloader;
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param callable $next
     * @throws \TwigYard\Exception\CannotAccessRemoteSourceException
     * @throws \TwigYard\Exception\InvalidDataFormatException
     * @throws \TwigYard\Exception\InvalidDataTypeException
     * @throws \TwigYard\Exception\InvalidSiteConfigException
     * @return \Psr\Http\Message\ResponseInterface $response
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        if (array_key_exists('data', $this->appState->getConfig())) {
            $conf = $this->appState->getConfig()['data'];
            $data = [];
            foreach ($conf as $var => $source) {
                if (!is_array($source)) {
                    $source = [
                        'type' => self::TYPE_LOCAL,
                        'format' => self::FORMAT_YAML,
                        'resource' => $source,
                    ];
                }
                if ($source['type'] === self::TYPE_LOCAL) {
                    $dataFile = $this->appState->getSiteDir() . '/' . $this->dataDir . '/' . $source['resource'];
                    if (!is_file($dataFile)) {
                        throw new InvalidSiteConfigException(sprintf('Missing data file: %s', $dataFile));
                    }
                    $content = file_get_contents($dataFile);
                } elseif ($source['type'] === self::TYPE_HTTP) {
                    $content = $this->curlDownloader->loadRemoteContent($source['resource']);
                } else {
                    throw new InvalidDataTypeException(sprintf('Data type [%s] is not supported.', $source['type']));
                }

                if ($source['format'] === self::FORMAT_YAML) {
                    try {
                        $data[$var] = Yaml::parse($content);
                    } catch (ParseException $e) {
                        throw new InvalidDataFormatException(sprintf(
                            'Invalid content of yml file %s with message %s',
                            $source['resource'],
                            $e->getMessage()
                        ));
                    }
                } elseif ($source['format'] === self::FORMAT_JSON) {
                    $data[$var] = \json_decode($content, true);
                    if (JSON_ERROR_NONE !== ($error = \json_last_error())) {
                        throw new InvalidDataFormatException(sprintf(
                            'Invalid content received from url %s with message %s',
                            $source['resource'],
                            json_last_error_msg()
                        ));
                    }
                } else {
                    throw new InvalidDataFormatException(sprintf(
                        'Data format [%s] is not supported.',
                        $source['format']
                    ));
                }
            }
            $this->appState->setData($data);
        }

        return $next($request, $response);
    }
}
