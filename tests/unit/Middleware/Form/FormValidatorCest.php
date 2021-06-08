<?php

namespace Unit\Middleware\Form;

use Prophecy\Argument\Token\AnyValueToken;
use Prophecy\Prophet;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use TwigYard\Component\HttpRequestSender;
use TwigYard\Component\ValidatorBuilderFactory;
use TwigYard\Middleware\Form\FormValidator;
use Zend\Diactoros\Response;

class FormValidatorCest
{
    public function testErrorsIsArrayOfStrings(\UnitTester $I)
    {
        $prophet = new Prophet();

        $errorListItem = $prophet->prophesize(ConstraintViolationInterface::class);
        $errorListItem->getMessage()->willReturn('message1');

        $errorsList = $prophet->prophesize(ConstraintViolationListInterface::class);
        $errorsList->willImplement(\IteratorAggregate::class);
        $errorsList->count()->willReturn(1);
        $errorsList->getIterator()->willReturn(new \ArrayIterator([$errorListItem->reveal()]));

        $validator = $prophet->prophesize(ValidatorInterface::class);
        $validator->validate(new AnyValueToken(), new AnyValueToken())->willReturn($errorsList->reveal());

        $validatorBuilderFactory = $prophet->prophesize(ValidatorBuilderFactory::class);
        $validatorBuilderFactory->createValidator(new AnyValueToken())->willReturn($validator->reveal());

        $formValidator = new FormValidator(
            $validatorBuilderFactory->reveal(),
            $this->getHttpRequestSender($prophet, ['success' => true])
        );

        $I->assertFalse(
            $formValidator->validate(
                ['field1' => [['Blank' => []]]],
                [],
                ['field1' => 'invalidData', 'csrf_token' => 'token'],
                'token',
                new Translator('en'),
                null
            )
        );

        $I->assertEquals(['field1' => ['message1']], $formValidator->getErrors());
    }

    public function testInvalidRecaptcha(\UnitTester $I)
    {
        $prophet = new Prophet();

        $errorsList = $prophet->prophesize(ConstraintViolationListInterface::class);
        $errorsList->willImplement(\IteratorAggregate::class);
        $errorsList->count()->willReturn(0);

        $validator = $prophet->prophesize(ValidatorInterface::class);
        $validator->validate(new AnyValueToken(), new AnyValueToken())->willReturn($errorsList->reveal());

        $validatorBuilderFactory = $prophet->prophesize(ValidatorBuilderFactory::class);
        $validatorBuilderFactory->createValidator(new AnyValueToken())->willReturn($validator->reveal());

        $formValidator = new FormValidator(
            $validatorBuilderFactory->reveal(),
            $this->getHttpRequestSender($prophet, ['success' => false])
        );

        $I->assertFalse(
            $formValidator->validate(
                ['field1' => [['Blank' => []]]],
                ['secret_key' => 'xxx'],
                ['field1' => 'invalidData', 'csrf_token' => 'token'],
                'token',
                new Translator('en'),
                'token'
            )
        );

        $I->assertEquals(
            'There was an error in recaptcha validation. Please send us an email instead.',
            $formValidator->getFlashMessage()
        );
    }

    /**
     * @param $prophet
     * @return HttpRequestSender
     */
    private function getHttpRequestSender($prophet, $responseData)
    {
        print_r(\GuzzleHttp\json_encode($responseData));
        $response = $prophet->prophesize(Response::class);
        $response->getStatusCode()->willReturn(200);
        $response->getBody()->willReturn(\GuzzleHttp\json_encode($responseData));

        $httpRequestSender = $prophet->prophesize(HttpRequestSender::class);
        $httpRequestSender->sendUrlencodedRequest(
            new AnyValueToken(),
            new AnyValueToken(),
            new AnyValueToken()
        )->willReturn($response->reveal());

        return $httpRequestSender->reveal();
    }
}
