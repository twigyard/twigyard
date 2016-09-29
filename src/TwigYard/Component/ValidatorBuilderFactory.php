<?php

namespace TwigYard\Component;

use Symfony\Component\Translation\Translator;
use Symfony\Component\Validator\Validation;

class ValidatorBuilderFactory
{
    /**
     * @var AppState
     */
    private $appState;

    /**
     * ValidatorFactory constructor.
     * @param \TwigYard\Component\AppState $appState
     */
    public function __construct(AppState $appState)
    {
        $this->appState = $appState;
    }

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
