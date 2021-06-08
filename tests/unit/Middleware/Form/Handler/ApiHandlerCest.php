<?php

namespace Unit\Middleware\Form\Handler;

use Prophecy\Argument;
use Prophecy\Prophet;
use TwigYard\Component\HttpRequestSender;
use TwigYard\Exception\InvalidSiteConfigException;
use TwigYard\Middleware\Form\Handler\ApiHandler;
use Zend\Diactoros\Response;

class ApiHandlerCest
{
    public function testValidateMandatoryConfig(\UnitTester $I)
    {
        $prophet = new Prophet();

        try {
            new ApiHandler(
                [
                    'type' => 'api',
                    'url' => 'https://api.example.com/trigger',
                    'method' => 'POST',
                    'data' => [
                        'apiToken' => 'notSoSecretFormToken',
                    ],
                ],
                $this->getHttpRequestSender($prophet, [])
            );
        } catch (InvalidSiteConfigException $e) {
            $I->fail($e->getMessage());
        }
    }

    public function testValidateAllConfig(\UnitTester $I)
    {
        $prophet = new Prophet();

        try {
            new ApiHandler(
                [
                    'type' => 'api',
                    'url' => 'https://api.example.com/users',
                    'method' => 'POST',
                    'data' => [
                        'name' => [
                            'form_value' => 'name',
                        ],
                        'email' => [
                            'form_value' => 'email',
                            'default' => 'anonymous@example.com',
                        ],
                        'fieldWithString' => [
                            'format' => 'string',
                            'form_value' => 'fieldWithString',
                        ],
                        'fieldWithStringExt' => [
                            'format' => [
                                'type' => 'string',
                            ],
                            'form_value' => 'fieldWithStringExt',
                        ],
                        'fieldWithInt' => [
                            'format' => 'int',
                            'form_value' => 'fieldWithInt',
                        ],
                        'fieldWithIntExt' => [
                            'format' => [
                                'type' => 'int',
                            ],
                            'form_value' => 'fieldWithIntExt',
                        ],
                        'fieldWithFloat' => [
                            'format' => 'float',
                            'form_value' => 'fieldWithFloat',
                        ],
                        'fieldWithFloatExt' => [
                            'format' => [
                                'type' => 'float',
                            ],
                            'form_value' => 'fieldWithFloatExt',
                        ],
                        'fieldWithBool' => [
                            'format' => 'bool',
                            'form_value' => 'fieldWithBool',
                        ],
                        'fieldWithBoolExt' => [
                            'format' => [
                                'type' => 'bool',
                            ],
                            'form_value' => 'fieldWithBoolExt',
                        ],
                        'fieldWithDateTimeExt' => [
                            'format' => [
                                'type' => 'datetime',
                                'in' => 'd.m.Y H:i',
                                'out' => 'Y-m-d H:i:s',
                            ],
                            'form_value' => 'fieldWithDateTimeExt',
                        ],
                        'apiToken' => 'notSoSecretFormToken',
                    ],
                    'headers' => [
                        'apiToken' => 'topSecretApiToken',
                    ],
                    'response' => [
                        'redirect_url_param' => 'redirectToUrlParam',
                    ],
                ],
                $this->getHttpRequestSender($prophet, [])
            );
        } catch (InvalidSiteConfigException $e) {
            $I->fail($e->getMessage());
        }
    }

    public function testRequest(\UnitTester $I)
    {
        $prophet = new Prophet();
        $apiHandler = new ApiHandler(
            [
                'type' => 'api',
                'url' => 'https://api.example.com/trigger',
                'method' => 'POST',
                'data' => [
                    'name' => [
                        'form_value' => 'name',
                    ],
                    'email' => [
                        'form_value' => 'email',
                        'default' => 'anonymous@example.com',
                    ],
                    'fieldWithDateTimeExt' => [
                        'format' => [
                            'type' => 'datetime',
                            'in' => 'd.m.Y H:i',
                            'out' => 'Y-m-d H:i:s',
                        ],
                        'form_value' => 'fieldWithDateTimeExt',
                    ],
                ],
            ],
            $this->getHttpRequestSender($prophet, [])
        );

        $response = $apiHandler->handle([
            'name' => 'Jan Novak',
            'fieldWithDateTimeExt' => '24.12.2018 12:00',
        ]);
        $I->assertEquals(null, $response);
    }

    public function testInvalidRequest(\UnitTester $I)
    {
        $prophet = new Prophet();

        try {
            $apiHandler = new ApiHandler(
                [
                    'type' => 'api',
                    'url' => 'https://api.example.com/trigger',
                    'method' => 'POST',
                    'data' => [
                        'name' => [
                            'form_value' => 'name',
                        ],
                        'email' => [
                            'form_value' => 'email',
                        ],
                    ],
                ],
                $this->getHttpRequestSender(
                    $prophet,
                    []
                )
            );

            $apiHandler->handle(['name' => 'Jan Novak']);
            $I->fail('Form API handler should fail');
        } catch (\Exception $ex) {
        }
    }

    public function testRequestWithRedirectResponse(\UnitTester $I)
    {
        $prophet = new Prophet();
        $apiHandler = new ApiHandler(
            [
                'type' => 'api',
                'url' => 'https://api.example.com/trigger',
                'method' => 'POST',
                'data' => [
                    'apiToken' => 'notSoSecretFormToken',
                ],
                'response' => [
                    'redirect_url_param' => 'redirectToUrlParam',
                ],
            ],
            $this->getHttpRequestSender(
                $prophet,
                ['redirectToUrlParam' => 'https://www.example.com/']
            )
        );

        $response = $apiHandler->handle([]);

        $I->assertEquals(302, $response->getStatusCode());
        $I->assertEquals('https://www.example.com/', $response->getHeaderLine('location'));
    }

    public function testRequestWithInvalidRedirectResponse(\UnitTester $I)
    {
        $prophet = new Prophet();

        try {
            $apiHandler = new ApiHandler(
                [
                    'type' => 'api',
                    'url' => 'https://api.example.com/trigger',
                    'method' => 'POST',
                    'data' => [
                        'apiToken' => 'notSoSecretFormToken',
                    ],
                    'response' => [
                        'redirect_url_param' => 'redirectToUrlParam',
                    ],
                ],
                $this->getHttpRequestSender(
                    $prophet,
                    ['redirectToWrongUrlParam' => 'https://www.example.com/']
                )
            );

            $apiHandler->handle([]);
            $I->fail('Form API handler should fail');
        } catch (\Exception $ex) {
        }
    }

    /**
     * @param $prophet
     * @return HttpRequestSender
     */
    private function getHttpRequestSender($prophet, $responseData)
    {
        $response = $prophet->prophesize(Response::class);
        $response->getStatusCode()->willReturn(200);
        $response->getBody()->willReturn(\GuzzleHttp\json_encode($responseData));

        $httpRequestSender = $prophet->prophesize(HttpRequestSender::class);
        $httpRequestSender->sendJsonRequest(
            Argument::type('string'),
            Argument::type('string'),
            Argument::type('array'),
            Argument::type('array')
        )->willReturn($response->reveal());

        return $httpRequestSender->reveal();
    }
}
