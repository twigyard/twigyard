<?php

namespace Unit\Middleware\Form;

use Prophecy\Argument\Token\AnyValueToken;
use Prophecy\Prophet;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use TwigYard\Component\ValidatorBuilderFactory;
use TwigYard\Middleware\Form\FormValidator;

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

        $formValidator = new FormValidator($validatorBuilderFactory->reveal());

        $I->assertFalse(
            $formValidator->validate(
                ['field1' => [['Blank' => []]]],
                ['field1' => 'invalidData', 'csrf_token' => 'token'],
                'token',
                new Translator('en')
            )
        );

        $I->assertEquals(['field1' => ['message1']], $formValidator->getErrors());
    }
}
