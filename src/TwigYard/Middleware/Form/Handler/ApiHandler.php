<?php

namespace TwigYard\Middleware\Form\Handler;

use TwigYard\Component\HttpRequestSender;
use TwigYard\Exception\InvalidSiteConfigException;
use Zend\Diactoros\Response;
use Zend\Diactoros\Response\RedirectResponse;

class ApiHandler implements HandlerInterface
{
    const CONFIG_TYPE = 'type';
    const CONFIG_TYPE_API = 'api';

    const CONFIG_URL = 'url';

    const CONFIG_METHOD = 'method';
    const CONFIG_METHOD_GET = 'GET';
    const CONFIG_METHOD_POST = 'POST';
    const CONFIG_METHOD_PUT = 'PUT';
    const CONFIG_METHOD_DELETE = 'DELETE';

    const CONFIG_DATA = 'data';
    const CONFIG_DATA_FORMAT = 'format';
    const CONFIG_DATA_FORMAT_TYPE = 'type';
    const CONFIG_DATA_FORMAT_IN = 'in';
    const CONFIG_DATA_FORMAT_OUT = 'out';
    const CONFIG_DATA_FORMAT_STRING = 'string';
    const CONFIG_DATA_FORMAT_INT = 'int';
    const CONFIG_DATA_FORMAT_FLOAT = 'float';
    const CONFIG_DATA_FORMAT_BOOL = 'bool';
    const CONFIG_DATA_FORMAT_DATETIME = 'datetime';
    const CONFIG_DATA_FORM_VALUE = 'form_value';
    const CONFIG_DATA_DEFAULT = 'default';

    const CONFIG_HEADERS = 'headers';

    const CONFIG_RESPONSE = 'response';
    const CONFIG_RESPONSE_REDIRECT_URL_PARAM = 'redirect_url_param';

    /**
     * @var array
     */
    private $config;

    /**
     * @var HttpRequestSender
     */
    private $httpRequestSender;

    /**
     * ApiHandler constructor.
     * @param array $config
     * @param HttpRequestSender $httpRequestSender
     * @throws InvalidSiteConfigException
     */
    public function __construct(array $config, HttpRequestSender $httpRequestSender)
    {
        $this->config = $config;
        $this->httpRequestSender = $httpRequestSender;

        $this->validateConfig();
    }

    /**
     * @param array $formData
     * @throws \HttpRequestException
     * @return Response
     */
    public function handle(array $formData)
    {
        $sendData = [];

        foreach ($this->config[self::CONFIG_DATA] as $dataKey => $dataValue) {
            if (is_string($dataValue) || is_numeric($dataValue)) {
                $sendData[$dataKey] = $dataValue;
            } else {
                $formValue = $dataValue[self::CONFIG_DATA_FORM_VALUE];

                if (array_key_exists($formValue, $formData)) {
                    $sendData[$dataKey] = $formData[$formValue];
                } else {
                    if (array_key_exists(self::CONFIG_DATA_DEFAULT, $dataValue)) {
                        $sendData[$dataKey] = $dataValue[self::CONFIG_DATA_DEFAULT];
                    } else {
                        throw new \InvalidArgumentException(
                            sprintf(
                                'Form API handler expected to map form field `%s` onto `%s` but `%s` is missing.',
                                $formValue,
                                $dataKey,
                                $formValue
                            )
                        );
                    }
                }

                if (
                    array_key_exists(self::CONFIG_DATA_FORMAT, $dataValue)
                    && is_string($dataValue[self::CONFIG_DATA_FORMAT])
                ) {
                    $dataFormat = $dataValue[self::CONFIG_DATA_FORMAT];

                    if (self::CONFIG_DATA_FORMAT_INT === $dataFormat) {
                        $sendData[$dataKey] = intval($sendData[$dataKey]);
                    } elseif (self::CONFIG_DATA_FORMAT_FLOAT === $dataFormat) {
                        $sendData[$dataKey] = floatval($sendData[$dataKey]);
                    } elseif (self::CONFIG_DATA_FORMAT_BOOL === $dataFormat) {
                        $sendData[$dataKey] = boolval($sendData[$dataKey]);
                    }
                }

                if (
                    array_key_exists(self::CONFIG_DATA_FORMAT, $dataValue)
                    && is_array($dataValue[self::CONFIG_DATA_FORMAT])
                ) {
                    $dataFormat = $dataValue[self::CONFIG_DATA_FORMAT][self::CONFIG_DATA_FORMAT_TYPE];
                    $dataFormatIn = $dataValue[self::CONFIG_DATA_FORMAT][self::CONFIG_DATA_FORMAT_IN];
                    $dataFormatOut = $dataValue[self::CONFIG_DATA_FORMAT][self::CONFIG_DATA_FORMAT_OUT];

                    if (self::CONFIG_DATA_FORMAT_INT === $dataFormat) {
                        $sendData[$dataKey] = intval($sendData[$dataKey]);
                    } elseif (self::CONFIG_DATA_FORMAT_FLOAT === $dataFormat) {
                        $sendData[$dataKey] = floatval($sendData[$dataKey]);
                    } elseif (self::CONFIG_DATA_FORMAT_BOOL === $dataFormat) {
                        $sendData[$dataKey] = boolval($sendData[$dataKey]);
                    } elseif (self::CONFIG_DATA_FORMAT_DATETIME === $dataFormat) {
                        $dateTime = \DateTime::createFromFormat($dataFormatIn, $sendData[$dataKey]);

                        if ($dateTime === false) {
                            throw new \InvalidArgumentException(
                                sprintf(
                                    'Form API handler is unable to convert form value `%s` of field `%s` from `%s` to `%s`.',
                                    $sendData[$dataKey],
                                    $dataKey,
                                    $dataFormatIn,
                                    $dataFormatOut
                                )
                            );
                        }

                        $sendData[$dataKey] = $dateTime->format($dataFormatOut);
                    }
                }
            }
        }

        $response = $this->httpRequestSender->sendRequest(
            $this->config[self::CONFIG_METHOD],
            $this->config[self::CONFIG_URL],
            $sendData,
            array_key_exists(self::CONFIG_HEADERS, $this->config) ? $this->config[self::CONFIG_HEADERS] : []
        );

        if (!in_array($response->getStatusCode(), [200, 201, 202, 203, 204])) {
            throw new \Exception('Form API handler request failed.');
        }

        $responseBody = json_decode($response->getBody(), true);

        if (
            array_key_exists(self::CONFIG_RESPONSE, $this->config)
            && array_key_exists(self::CONFIG_RESPONSE_REDIRECT_URL_PARAM, $this->config[self::CONFIG_RESPONSE])
        ) {
            $redirectUrlParam = $this->config[self::CONFIG_RESPONSE][self::CONFIG_RESPONSE_REDIRECT_URL_PARAM];

            if (!array_key_exists($redirectUrlParam, $responseBody)) {
                throw new \Exception(
                    sprintf('Form API handler request expected to receive parameters `%s`.', $redirectUrlParam)
                );
            }

            return new RedirectResponse($responseBody[$redirectUrlParam]);
        }
    }

    /**
     * @throws InvalidSiteConfigException
     */
    private function validateConfig(): void
    {
        if (
            array_key_exists(self::CONFIG_TYPE, $this->config)
            && $this->config[self::CONFIG_TYPE] === self::CONFIG_TYPE_API
        ) {
            $this->validateConfigUrl();
            $this->validateConfigMethod();
            $this->validateConfigData();
            $this->validateConfigHeaders();
            $this->validateConfigResponse();
        }
    }

    /**
     * @throws InvalidSiteConfigException
     */
    private function validateConfigUrl(): void
    {
        if (
            !array_key_exists(self::CONFIG_URL, $this->config)
            || !is_string($this->config[self::CONFIG_URL])
        ) {
            throw new InvalidSiteConfigException(
                sprintf(
                    'Form API handler option `%s` is not set or is not of type string.',
                    self::CONFIG_URL
                )
            );
        }
    }

    /**
     * @throws InvalidSiteConfigException
     */
    private function validateConfigMethod(): void
    {
        if (
            !array_key_exists(self::CONFIG_METHOD, $this->config)
            || !is_string($this->config[self::CONFIG_METHOD])
        ) {
            throw new InvalidSiteConfigException(
                sprintf(
                    'Form API handler option `%s` is not set or is not of type string.',
                    self::CONFIG_METHOD
                )
            );
        }

        $supportedMethods = [
            self::CONFIG_METHOD_GET,
            self::CONFIG_METHOD_POST,
            self::CONFIG_METHOD_PUT,
            self::CONFIG_METHOD_DELETE,
        ];

        if (
            array_key_exists(self::CONFIG_METHOD, $this->config)
            && !in_array($this->config[self::CONFIG_METHOD], $supportedMethods)
        ) {
            throw new InvalidSiteConfigException(
                sprintf(
                    'Form API handler option `%s` is expecting one of [%s], `%s` given.',
                    self::CONFIG_METHOD,
                    implode(', ', $supportedMethods),
                    $this->config[self::CONFIG_METHOD]
                )
            );
        }
    }

    /**
     * @throws InvalidSiteConfigException
     */
    private function validateConfigData(): void
    {
        if (array_key_exists(self::CONFIG_DATA, $this->config) && is_array($this->config[self::CONFIG_DATA])) {
            $configData = $this->config[self::CONFIG_DATA];

            foreach ($configData as $dataKey => $dataValue) {
                if (is_array($dataValue)) {
                    $supportedTypes = [
                        self::CONFIG_DATA_FORMAT_STRING,
                        self::CONFIG_DATA_FORMAT_INT,
                        self::CONFIG_DATA_FORMAT_FLOAT,
                        self::CONFIG_DATA_FORMAT_BOOL,
                    ];
                    $supportedTypesExt = array_merge(
                        $supportedTypes,
                        [self::CONFIG_DATA_FORMAT_DATETIME]
                    );

                    if (
                        array_key_exists(self::CONFIG_DATA_FORMAT, $dataValue)
                        && is_string($dataValue[self::CONFIG_DATA_FORMAT])
                        && !in_array($dataValue[self::CONFIG_DATA_FORMAT], $supportedTypes)
                    ) {
                        throw new InvalidSiteConfigException(
                            sprintf(
                                'Form API handler option `%s.%s.%s` is expecting one of [%s], `%s` given.',
                                self::CONFIG_DATA,
                                $dataKey,
                                self::CONFIG_DATA_FORMAT,
                                implode(', ', $supportedTypes),
                                $dataValue[self::CONFIG_DATA_FORMAT]
                            )
                        );
                    }

                    if (
                        array_key_exists(self::CONFIG_DATA_FORMAT, $dataValue)
                        && is_array($dataValue[self::CONFIG_DATA_FORMAT])
                    ) {
                        $dataFormat = $dataValue[self::CONFIG_DATA_FORMAT];

                        if (
                            array_key_exists(self::CONFIG_DATA_FORMAT_TYPE, $dataFormat)
                            && !in_array($dataFormat[self::CONFIG_DATA_FORMAT_TYPE], $supportedTypesExt)
                        ) {
                            throw new InvalidSiteConfigException(
                                sprintf(
                                    'Form API handler option `%s.%s.%s.%s` is expecting one of [%s], `%s` given.',
                                    self::CONFIG_DATA,
                                    $dataKey,
                                    self::CONFIG_DATA_FORMAT,
                                    self::CONFIG_DATA_FORMAT_TYPE,
                                    implode(', ', $supportedTypesExt),
                                    $dataFormat[self::CONFIG_DATA_FORMAT_TYPE]
                                )
                            );
                        }

                        if (
                            array_key_exists(self::CONFIG_DATA_FORMAT_TYPE, $dataFormat)
                            && $dataFormat[self::CONFIG_DATA_FORMAT_TYPE] === self::CONFIG_DATA_FORMAT_DATETIME
                            && (
                                !array_key_exists(self::CONFIG_DATA_FORMAT_IN, $dataFormat)
                                || !is_string($dataFormat[self::CONFIG_DATA_FORMAT_IN])
                            )
                        ) {
                            throw new InvalidSiteConfigException(
                                sprintf(
                                    'Form API handler option `%s.%s.%s.%s` is not set or is not of types string',
                                    self::CONFIG_DATA,
                                    $dataKey,
                                    self::CONFIG_DATA_FORMAT,
                                    self::CONFIG_DATA_FORMAT_IN
                                )
                            );
                        }

                        if (
                            array_key_exists(self::CONFIG_DATA_FORMAT_TYPE, $dataFormat)
                            && $dataFormat[self::CONFIG_DATA_FORMAT_TYPE] === self::CONFIG_DATA_FORMAT_DATETIME
                            && (
                                !array_key_exists(self::CONFIG_DATA_FORMAT_OUT, $dataFormat)
                                || !is_string($dataFormat[self::CONFIG_DATA_FORMAT_OUT])
                            )
                        ) {
                            throw new InvalidSiteConfigException(
                                sprintf(
                                    'Form API handler option `%s.%s.%s.%s` is not set or is not of types string',
                                    self::CONFIG_DATA,
                                    $dataKey,
                                    self::CONFIG_DATA_FORMAT,
                                    self::CONFIG_DATA_FORMAT_OUT
                                )
                            );
                        }
                    }

                    if (
                        (
                            count($dataValue) === 1
                            && array_key_exists(self::CONFIG_DATA_FORM_VALUE, $dataValue)
                        ) || (
                            count($dataValue) === 2
                            && array_key_exists(self::CONFIG_DATA_FORMAT, $dataValue)
                            && array_key_exists(self::CONFIG_DATA_FORM_VALUE, $dataValue)
                        )
                    ) {
                        $formValue = $dataValue[self::CONFIG_DATA_FORM_VALUE];

                        if (!is_string($formValue) && !is_numeric($formValue)) {
                            throw new InvalidSiteConfigException(
                                sprintf(
                                    'Form API handler option `%s.%s.%s` is not set or is not of types string or int.',
                                    self::CONFIG_DATA,
                                    $dataKey,
                                    self::CONFIG_DATA_FORM_VALUE
                                )
                            );
                        }
                    } elseif (
                        (
                            count($dataValue) === 2
                            && array_key_exists(self::CONFIG_DATA_FORM_VALUE, $dataValue)
                            && array_key_exists(self::CONFIG_DATA_DEFAULT, $dataValue)
                        ) || (
                            count($dataValue) === 3
                            && array_key_exists(self::CONFIG_DATA_FORMAT, $dataValue)
                            && array_key_exists(self::CONFIG_DATA_FORM_VALUE, $dataValue)
                            && array_key_exists(self::CONFIG_DATA_DEFAULT, $dataValue)
                        )
                    ) {
                        $formValue = $dataValue[self::CONFIG_DATA_FORM_VALUE];
                        $defaultValue = $dataValue[self::CONFIG_DATA_DEFAULT];

                        if (!is_string($formValue) && !is_numeric($formValue)) {
                            throw new InvalidSiteConfigException(
                                sprintf(
                                    'Form API handler option `%s.%s.%s` is not set or is not of types string or int.',
                                    self::CONFIG_DATA,
                                    $dataKey,
                                    self::CONFIG_DATA_FORM_VALUE
                                )
                            );
                        }

                        if (!is_string($defaultValue) && !is_numeric($defaultValue)) {
                            throw new InvalidSiteConfigException(
                                sprintf(
                                    'Form API handler option `%s.%s.%s` is not set or is not of types string or int.',
                                    self::CONFIG_DATA,
                                    $dataKey,
                                    self::CONFIG_DATA_DEFAULT
                                )
                            );
                        }
                    } else {
                        throw new InvalidSiteConfigException(
                            sprintf(
                                'Form API handler option `%s.%s` supports only `%s` or `%s` with `%s` options.',
                                self::CONFIG_DATA,
                                $dataKey,
                                self::CONFIG_DATA_FORM_VALUE,
                                self::CONFIG_DATA_FORM_VALUE,
                                self::CONFIG_DATA_DEFAULT
                            )
                        );
                    }
                } elseif (!is_string($dataValue) && !is_numeric($dataValue)) {
                    throw new InvalidSiteConfigException(
                        sprintf(
                            'Form API handler option `%s.%s` is not set or is not of type string or int.',
                            self::CONFIG_DATA,
                            $dataKey
                        )
                    );
                }
            }
        } else {
            throw new InvalidSiteConfigException(
                sprintf(
                    'Form API handler option `%s` is not set or is not of type array.',
                    self::CONFIG_DATA
                )
            );
        }
    }

    /**
     * @throws InvalidSiteConfigException
     */
    private function validateConfigHeaders()
    {
        if (array_key_exists(self::CONFIG_HEADERS, $this->config)) {
            $configHeaders = $this->config[self::CONFIG_HEADERS];

            if (is_array($configHeaders)) {
                foreach ($configHeaders as $headerKey => $headerValue) {
                    if (!is_string($headerValue) && !is_numeric($headerValue)) {
                        throw new InvalidSiteConfigException(
                            sprintf(
                                'Form API handler option `%s.%s` is not set or is not of types string or int.',
                                self::CONFIG_HEADERS,
                                $headerKey
                            )
                        );
                    }
                }
            } else {
                throw new InvalidSiteConfigException(
                    sprintf(
                        'Form API handler option `%s` is not set or is not of type array.',
                        self::CONFIG_HEADERS
                    )
                );
            }
        }
    }

    /**
     * @throws InvalidSiteConfigException
     */
    private function validateConfigResponse(): void
    {
        if (array_key_exists(self::CONFIG_RESPONSE, $this->config)) {
            $configResponse = $this->config[self::CONFIG_RESPONSE];

            if (is_array($configResponse)) {
                if (
                    count($configResponse) === 1
                    && array_key_exists(self::CONFIG_RESPONSE_REDIRECT_URL_PARAM, $configResponse)
                ) {
                    $redirectUrlParam = $configResponse[self::CONFIG_RESPONSE_REDIRECT_URL_PARAM];

                    if (!is_string($redirectUrlParam) && !is_numeric($redirectUrlParam)) {
                        throw new InvalidSiteConfigException(
                            sprintf(
                                'Form API handler option `%s.%s` is not set or is not of type string or integer.',
                                self::CONFIG_RESPONSE,
                                self::CONFIG_RESPONSE_REDIRECT_URL_PARAM
                            )
                        );
                    }
                } else {
                    throw new InvalidSiteConfigException(
                        sprintf(
                            'Form API handler option `%s` supports only `%s` option.',
                            self::CONFIG_RESPONSE,
                            self::CONFIG_RESPONSE_REDIRECT_URL_PARAM
                        )
                    );
                }
            } else {
                throw new InvalidSiteConfigException(
                    sprintf(
                        'Form API handler option `%s` is not set or is not of type array.',
                        self::CONFIG_RESPONSE
                    )
                );
            }
        }
    }
}
