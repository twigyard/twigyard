<?php

namespace TwigYard\Middleware\Form;

use Symfony\Component\Translation\Translator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Mapping\Loader\AbstractLoader;
use TwigYard\Component\ValidatorBuilderFactory;
use TwigYard\Middleware\Form\Exception\ConstraintNotFoundException;

class FormValidator
{
    const FLASH_MESSAGE_CSRF_ERROR = 'The form validity has expired. Give it one more try.';
    const FLASH_MESSAGE_VALIDATION_ERROR = 'The form cannot be saved, please check marked values.';

    const FLASH_MESSAGE_TYPE_SUCCESS = 'success';
    const FLASH_MESSAGE_TYPE_ERROR_VALIDATION = 'error-validation';
    const FLASH_MESSAGE_TYPE_ERROR_EXPIRED_TOKEN = 'error-expired-token';

    const CONSTRAINTS_NAMESPACES = [AbstractLoader::DEFAULT_NAMESPACE];

    /**
     * @var array
     */
    private $errors;

    /**
     * @var string
     */
    private $flashMessage;

    /**
     * @var string
     */
    private $flashMessageType;

    /**
     * @var ValidatorBuilderFactory
     */
    private $validatorFactory;

    /**
     * FormValidator constructor.
     * @param ValidatorBuilderFactory $validatorFactory
     */
    public function __construct(ValidatorBuilderFactory $validatorFactory)
    {
        $this->validatorFactory = $validatorFactory;
        $this->errors = [];
    }

    /**
     * @param array $formFields
     * @param array $formData
     * @param string $csrfValue
     * @param Translator $translator
     * @throws ConstraintNotFoundException
     * @return bool
     */
    public function validate(array $formFields, array $formData, string $csrfValue, Translator $translator): bool
    {
        if ($formData[FormMiddleware::CSRF_FIELD_NAME] !== $csrfValue) {
            $this->flashMessage = $translator->trans(self::FLASH_MESSAGE_CSRF_ERROR);
            $this->flashMessageType = self::FLASH_MESSAGE_TYPE_ERROR_EXPIRED_TOKEN;

            return false;
        }

        $validator = $this->validatorFactory->createValidator($translator);
        foreach ($formFields as $fieldName => $constraints) {
            $newErrors = $validator->validate(
                !empty($formData[$fieldName]) ? $formData[$fieldName] : null,
                $this->parseNodes($formFields[$fieldName])
            );

            if ($newErrors->count() > 0) {
                $textErrors = [];
                foreach ($newErrors as $error) {
                    $textErrors[] = $error->getMessage();
                }
                $this->errors[$fieldName] = $textErrors;
            }
        }

        if (count($this->errors) > 0) {
            $this->flashMessage = $translator->trans(self::FLASH_MESSAGE_VALIDATION_ERROR);
            $this->flashMessageType = self::FLASH_MESSAGE_TYPE_ERROR_VALIDATION;

            return false;
        }

        return true;
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @return string
     */
    public function getFlashMessage(): string
    {
        return $this->flashMessage;
    }

    /**
     * @return string
     */
    public function getFlashMessageType(): string
    {
        return $this->flashMessageType;
    }

    /**
     * @param array $nodes
     * @throws ConstraintNotFoundException
     * @return array
     */
    private function parseNodes(array $nodes): array
    {
        $values = [];

        foreach ($nodes as $name => $childNodes) {
            if (is_numeric($name) && is_array($childNodes) && 1 === count($childNodes)) {
                $options = current($childNodes);

                if (is_array($options)) {
                    $options = $this->parseNodes($options);
                }

                $values[] = $this->getConstraint(key($childNodes), $options);
            } else {
                if (is_array($childNodes)) {
                    $childNodes = $this->parseNodes($childNodes);
                }

                $values[$name] = $childNodes;
            }
        }

        return $values;
    }

    /**
     * @param string $name
     * @param mixed $options
     * @throws ConstraintNotFoundException
     * @return mixed
     */
    private function getConstraint(string $name, $options = null)
    {
        foreach (self::CONSTRAINTS_NAMESPACES as $namespace) {
            $className = $namespace . $name;

            if (class_exists($className)) {
                return new $className($options);
            }
        }

        throw new ConstraintNotFoundException(
            printf('The constraint %s was not found at any defined namespace.', $name)
        );
    }
}
