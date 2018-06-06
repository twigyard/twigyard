<?php

namespace TwigYard\Middleware\Form\Handler;

use GuzzleHttp\Exception\RequestException;
use TwigYard\Component\AppState;
use TwigYard\Exception\InvalidSiteConfigException;
use Zend\Diactoros\Request;
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
     * @var AppState
     */
    private $appState;

    /**
     * @param array $config
     * @param AppState $appState
     * @throws InvalidSiteConfigException
     */
    public function __construct(
        array $config,
        AppState $appState
    ) {
        $this->config = $config;
        $this->appState = $appState;

        $this->validateConfig();
    }

    /**
     * @param array $formData
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
            }
        }

        $response = (new \GuzzleHttp\Client())->request(
            $this->config[self::CONFIG_METHOD],
            $this->config[self::CONFIG_URL],
            [
                'headers' => $this->config[self::CONFIG_HEADERS] ?: [],
                'form_params' => $sendData,
            ]
        );

        if (!in_array($response->getStatusCode(), [200, 201, 202, 203, 204])) {
            throw new RequestException('Form API handler request failed.', new Request(), $response, null, $sendData);
        }

        $responseBody = json_decode($response->getBody(), true);

        if (
            array_key_exists(self::CONFIG_RESPONSE, $this->config)
            && array_key_exists(self::CONFIG_RESPONSE_REDIRECT_URL_PARAM, $this->config[self::CONFIG_RESPONSE])
        ) {
            $redirectUrlParam = $this->config[self::CONFIG_RESPONSE][self::CONFIG_RESPONSE_REDIRECT_URL_PARAM];

            if (!array_key_exists($redirectUrlParam, $responseBody)) {
                throw new RequestException(
                    sprintf('Form API handler request expected to receive parameters `%s`.', $redirectUrlParam),
                    new Request(),
                    $response
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
                    if (
                        count($dataValue) === 1
                        && array_key_exists(self::CONFIG_DATA_FORM_VALUE, $dataValue)
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
                        count($dataValue) === 2
                        && array_key_exists(self::CONFIG_DATA_FORM_VALUE, $dataValue)
                        && array_key_exists(self::CONFIG_DATA_DEFAULT, $dataValue)
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
                                'Form API handler option `%s.%s` is not set or is not of type string.',
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
