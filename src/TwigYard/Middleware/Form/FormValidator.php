<?php

namespace TwigYard\Middleware\Form;

use Symfony\Component\Translation\Translator;
use Symfony\Component\Validator\Mapping\Loader\AbstractLoader;
use TwigYard\Component\HttpRequestSender;
use TwigYard\Component\ValidatorBuilderFactory;
use TwigYard\Middleware\Form\Exception\ConstraintNotFoundException;

class FormValidator
{
    const FLASH_MESSAGE_CSRF_ERROR = 'The form validity has expired. Give it one more try.';
    const FLASH_MESSAGE_VALIDATION_ERROR = 'The form cannot be saved, please check marked values.';
    const FLASH_MESSAGE_RECAPTCHA_ERROR = 'There was an error in recaptcha validation. Please send us an email instead.';

    const FLASH_MESSAGE_TYPE_SUCCESS = 'success';
    const FLASH_MESSAGE_TYPE_ERROR_VALIDATION = 'error-validation';
    const FLASH_MESSAGE_TYPE_ERROR_EXPIRED_TOKEN = 'error-expired-token';

    const CONSTRAINTS_NAMESPACES = [AbstractLoader::DEFAULT_NAMESPACE];

    const RECAPTCHA_VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

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
     * @var HttpRequestSender
     */
    private $httpRequestSender;

    /**
     * @var ValidatorBuilderFactory
     */
    private $validatorFactory;

    /**
     * FormValidator constructor.
     */
    public function __construct(
        ValidatorBuilderFactory $validatorFactory,
        HttpRequestSender $httpRequestSender
    ) {
        $this->httpRequestSender = $httpRequestSender;
        $this->validatorFactory = $validatorFactory;
        $this->errors = [];
    }

    /**
     * @throws ConstraintNotFoundException
     */
    public function validate(
        array $formFields,
        array $recaptcha,
        array $formData,
        string $csrfValue,
        Translator $translator,
        ?string $recaptchaResponse = null
    ): bool {
        if ($formData[FormMiddleware::CSRF_FIELD_NAME] !== $csrfValue) {
            $this->flashMessage = $translator->trans(self::FLASH_MESSAGE_CSRF_ERROR);
            $this->flashMessageType = self::FLASH_MESSAGE_TYPE_ERROR_EXPIRED_TOKEN;

            return false;
        }

        if (!empty($recaptcha['secret_key'])) {
            $response = $this->httpRequestSender->sendUrlencodedRequest(
                self::RECAPTCHA_VERIFY_URL,
                [
                    'secret' => $recaptcha['secret_key'],
                    'response' => $recaptchaResponse,
                ],
            );
            $responseBody = json_decode($response->getBody(), true);
            if (!$responseBody['success']) {
                $this->flashMessage = $translator->trans(self::FLASH_MESSAGE_RECAPTCHA_ERROR);
                $this->flashMessageType = self::FLASH_MESSAGE_TYPE_ERROR_VALIDATION;

                return false;
            }
        }

        $validator = $this->validatorFactory->createValidator($translator);
        foreach ($formFields as $fieldName => $constraints) {
            $newErrors = $validator->validate(
                isset($formData[$fieldName]) ? $formData[$fieldName] : null,
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

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getFlashMessage(): string
    {
        return $this->flashMessage;
    }

    public function getFlashMessageType(): string
    {
        return $this->flashMessageType;
    }

    /**
     * @throws ConstraintNotFoundException
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

        throw new ConstraintNotFoundException(sprintf('The constraint %s was not found at any defined namespace.', $name));
    }
}
