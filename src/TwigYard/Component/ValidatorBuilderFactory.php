<?php

namespace TwigYard\Component;

use Symfony\Component\Translation\Translator;
use Symfony\Component\Validator\Validation;

class ValidatorBuilderFactory
{
    /**
     * @param Translator $translator
     * @return \Symfony\Component\Validator\Validator\ValidatorInterface
     */
    public function createValidator(Translator $translator)
    {
        return Validation::createValidatorBuilder()
            ->setTranslator($translator)
            ->setTranslationDomain('messages')
            ->getValidator();
    }
}
