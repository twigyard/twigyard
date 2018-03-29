<?php

namespace TwigYard\Component;

use Symfony\Component\Translation\Translator;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ValidatorBuilderFactory
{
    /**
     * @param Translator $translator
     * @return ValidatorInterface
     */
    public function createValidator(Translator $translator): ValidatorInterface
    {
        return Validation::createValidatorBuilder()
            ->setTranslator($translator)
            ->setTranslationDomain('messages')
            ->getValidator();
    }
}
