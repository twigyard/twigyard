<?php

namespace TwigYard\Middleware\Data;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use TwigYard\Component\AppState;
use TwigYard\Component\CurlDownloader;
use TwigYard\Exception\CannotAccessRemoteSourceException;
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
     * @var AppState
     */
    private $appState;

    /**
     * @var CurlDownloader
     */
    private $curlDownloader;

    /**
     * DataMiddleware constructor.
     */
    public function __construct(AppState $appState, string $dataDir, CurlDownloader $curlDownloader)
    {
        $this->dataDir = $dataDir;
        $this->appState = $appState;
        $this->curlDownloader = $curlDownloader;
    }

    /**
     * @throws CannotAccessRemoteSourceException
     * @throws InvalidDataFormatException
     * @throws InvalidDataTypeException
     * @throws InvalidSiteConfigException
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        if (array_key_exists('data', $this->appState->getMiddlewareConfig())) {
            $conf = $this->appState->getMiddlewareConfig()['data'];
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

                if (!$content) {
                    throw new InvalidDataFormatException(sprintf('Content of file %s could not be laoded', $source['resource']));
                }

                if ($source['format'] === self::FORMAT_YAML) {
                    try {
                        $data[$var] = Yaml::parse($content);
                    } catch (ParseException $e) {
                        throw new InvalidDataFormatException(sprintf('Invalid content of yml file %s with message %s', $source['resource'], $e->getMessage()));
                    }
                } elseif ($source['format'] === self::FORMAT_JSON) {
                    $data[$var] = \json_decode($content, true);
                    if (JSON_ERROR_NONE !== ($error = \json_last_error())) {
                        throw new InvalidDataFormatException(sprintf('Invalid content received from url %s with message %s', $source['resource'], json_last_error_msg()));
                    }
                } else {
                    throw new InvalidDataFormatException(sprintf('Data format [%s] is not supported.', $source['format']));
                }
            }
            $this->appState->setData($data);
        }

        return $next($request, $response);
    }
}
