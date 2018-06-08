<?php

namespace TwigYard\Middleware\Form;

use Dflydev\FigCookies\FigResponseCookies;
use Dflydev\FigCookies\SetCookie;
use Nette\Utils\FileSystem;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Translation\Translator;
use TwigYard\Component\AppState;
use TwigYard\Component\CsrfTokenGenerator;
use TwigYard\Component\SiteTranslatorFactory;
use TwigYard\Component\TranslatorFactory;
use TwigYard\Exception\MissingAppStateAttributeException;
use TwigYard\Middleware\Form\Exception\InvalidFormNameException;
use TwigYard\Middleware\Form\Exception\LogDirectoryNotWritableException;
use Zend\Diactoros\Response;

class FormMiddleware
{
    const CSRF_COOKIE_NAME = 'twigyard_csrf_token';
    const CSRF_COOKIE_TTL = 2 * 60 * 60;    // in seconds
    const CSRF_FIELD_NAME = 'csrf_token';
    const FLASH_MESSAGE_COOKIE_NAME = 'twigyard_flash_message';
    const FLASH_MESSAGE_TYPE_COOKIE_NAME = 'twigyard_flash_message_type';
    const FLASH_MESSAGE_COOKIE_TTL = 10;    // in seconds
    const FLASH_MESSAGE_DEFAULT_SUCCESS = 'The form was successfully sent, thank you!';

    /**
     * @var AppState
     */
    private $appState;

    /**
     * @var CsrfTokenGenerator
     */
    private $csrfTokenGenerator;

    /**
     * @var FormValidator
     */
    private $formValidator;

    /**
     * @var FormHandlerFactory
     */
    private $formHandlerFactory;

    /**
     * @var TranslatorFactory
     */
    private $translatorFactory;

    /**
     * @var SiteTranslatorFactory
     */
    private $siteTranslatorFactory;

    /**
     * @var string
     */
    private $logDir;

    /**
     * FormMiddleware constructor.
     * @param AppState $appState
     * @param CsrfTokenGenerator $csrfTokenGenerator
     * @param FormValidator $formValidator
     * @param FormHandlerFactory $formHandlerFactory
     * @param TranslatorFactory $translatorFactory
     * @param SiteTranslatorFactory $siteTranslatorFactory
     * @param string $logDir
     */
    public function __construct(
        AppState $appState,
        CsrfTokenGenerator $csrfTokenGenerator,
        FormValidator $formValidator,
        FormHandlerFactory $formHandlerFactory,
        TranslatorFactory $translatorFactory,
        SiteTranslatorFactory $siteTranslatorFactory,
        string $logDir
    ) {
        $this->appState = $appState;
        $this->csrfTokenGenerator = $csrfTokenGenerator;
        $this->formValidator = $formValidator;
        $this->formHandlerFactory = $formHandlerFactory;
        $this->translatorFactory = $translatorFactory;
        $this->siteTranslatorFactory = $siteTranslatorFactory;
        $this->logDir = $logDir;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable $next
     * @throws Exception\ConstraintNotFoundException
     * @throws Exception\InvalidFormHandlerException
     * @throws InvalidFormNameException
     * @throws LogDirectoryNotWritableException
     * @throws MissingAppStateAttributeException
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        if (!isset($this->appState->getConfig()['form'])) {
            return $next($request, $response);
        }

        $appFormData = [];
        $csrfToken = $this->csrfTokenGenerator->generateToken();
        foreach ($this->appState->getConfig()['form'] as $formName => $formConf) {
            foreach ($formConf['handlers'] as $handler) {
                if ($handler['type'] === FormHandlerFactory::TYPE_LOG) {
                    if (!file_exists($this->appState->getSiteDir() . '/' . $this->logDir)) {
                        FileSystem::createDir($this->appState->getSiteDir() . '/' . $this->logDir);
                    }

                    if (!is_writable($this->appState->getSiteDir() . '/' . $this->logDir)) {
                        throw new LogDirectoryNotWritableException();
                    }
                }
            }
            if (!preg_match('#^[a-z0-9_]+$#', $formName)) {
                throw new InvalidFormNameException();
            }
            $appFormData[$formName]['data']['csrf_token'] = $csrfToken;
            if (isset($formConf['anchor'])) {
                $appFormData[$formName]['anchor'] = $formConf['anchor'];
            }
            if (isset($request->getCookieParams()[self::FLASH_MESSAGE_COOKIE_NAME]) &&
                isset($request->getCookieParams()[self::FLASH_MESSAGE_TYPE_COOKIE_NAME])
            ) {
                $appFormData[$formName]['flash_message'] =
                    $request->getCookieParams()[self::FLASH_MESSAGE_COOKIE_NAME];
                $appFormData[$formName]['flash_message_type'] =
                    $request->getCookieParams()[self::FLASH_MESSAGE_TYPE_COOKIE_NAME];
            }
        }

        if (strtolower($request->getMethod()) === 'post') {
            foreach ($this->appState->getConfig()['form'] as $formName => $formConf) {
                if (isset($request->getParsedBody()[$formName])) {
                    $formData = $request->getParsedBody()[$formName];
                    $appFormData[$formName]['data'] = $formData;
                    $appFormData[$formName]['data']['csrf_token'] = $csrfToken;

                    $csrfCookieValue = null;
                    if (isset($request->getCookieParams()[self::CSRF_COOKIE_NAME])) {
                        $csrfCookieValue = $request->getCookieParams()[self::CSRF_COOKIE_NAME];
                    }

                    $translator = $this->translatorFactory->getTranslator($this->appState->getLocale());

                    if ($this->formValidator->validate(
                        isset($formConf['fields']) && is_array($formConf['fields']) ? $formConf['fields'] : [],
                        $formData,
                        $csrfCookieValue,
                        $translator
                    )) {
                        $this->appState->setForm($appFormData);
                        foreach ($formConf['handlers'] as $conf) {
                            $handler = $this->formHandlerFactory->build($conf, $this->appState->getSiteParameters());
                            $response = $handler->handle($formData);

                            if ($response) {
                                return $response;
                            }
                        }

                        return $this->getSubmitSuccessResponse(
                            $formName,
                            $formConf,
                            $request->getUri()->getPath(),
                            $translator
                        );
                    }

                    $appFormData[$formName]['flash_message'] = $this->formValidator->getFlashMessage();
                    $appFormData[$formName]['flash_message_type'] = $this->formValidator->getFlashMessageType();
                    $appFormData[$formName]['errors'] = $this->formValidator->getErrors();
                    break;
                }
            }
        }

        $this->appState->setForm($appFormData);
        $response = $next($request, $response);
        $response = FigResponseCookies::set($response, $this->getCsrfCookie($csrfToken));
        $response = $this->setFlashMessageCookie($response);

        return $response;
    }

    /**
     * @param string $formName
     * @param array $formConf
     * @param string $path
     * @param Translator $translator
     * @throws MissingAppStateAttributeException
     * @return ResponseInterface
     */
    private function getSubmitSuccessResponse(string $formName, array $formConf, string $path, Translator $translator): ResponseInterface
    {
        $response = (new Response())
            ->withStatus(302)
            ->withHeader('Location', sprintf(
                '%s%s?formSent=%s%s',
                $this->appState->isSingleLanguage() ? '' : '/' . $this->appState->getLanguageCode(),
                $path,
                $formName,
                !empty($formConf['anchor']) ? '#' . $formConf['anchor'] : ''
            ));

        if (!empty($formConf['success_flash_message'])) {
            $siteTranslator = $this->siteTranslatorFactory->getTranslator(
                $this->appState->getSiteDir(),
                $this->appState->getLocale()
            );
            $message = $siteTranslator->trans($formConf['success_flash_message']);
        } else {
            $message = $translator->trans(self::FLASH_MESSAGE_DEFAULT_SUCCESS);
        }

        return $this->setFlashMessageCookie($response, $message, FormValidator::FLASH_MESSAGE_TYPE_SUCCESS);
    }

    /**
     * @param string $csrfToken
     * @return SetCookie
     */
    private function getCsrfCookie(string $csrfToken): SetCookie
    {
        $csrfCookie = SetCookie::create(self::CSRF_COOKIE_NAME)
            ->withValue($csrfToken)
            ->withExpires((new \DateTime())->modify(sprintf('+%d seconds', self::CSRF_COOKIE_TTL)))
            ->withHttpOnly(true);

        if ($this->appState->getScheme() == 'https') {
            $csrfCookie = $csrfCookie->withSecure(true);
        }

        return $csrfCookie;
    }

    /**
     * @param ResponseInterface $response
     * @param string|null $flashMessage
     * @param string|null $flashMessageType
     * @return ResponseInterface
     */
    private function setFlashMessageCookie(ResponseInterface $response, ?string $flashMessage = null, ?string $flashMessageType = null): ResponseInterface
    {
        $cookie = SetCookie::create(self::FLASH_MESSAGE_COOKIE_NAME)
            ->withValue($flashMessage)
            ->withExpires((new \DateTime())->modify(sprintf('+%d seconds', self::FLASH_MESSAGE_COOKIE_TTL)))
            ->withHttpOnly(true);

        $typeCookie = SetCookie::create(self::FLASH_MESSAGE_TYPE_COOKIE_NAME)
            ->withValue($flashMessageType)
            ->withExpires((new \DateTime())->modify(sprintf('+%d seconds', self::FLASH_MESSAGE_COOKIE_TTL)))
            ->withHttpOnly(true);

        if ($this->appState->getScheme() == 'https') {
            $cookie = $cookie->withSecure(true);
            $typeCookie = $typeCookie->withSecure(true);
        }

        $modifiedResponse = FigResponseCookies::set($response, $cookie);
        $modifiedResponse = FigResponseCookies::set($modifiedResponse, $typeCookie);

        return $modifiedResponse;
    }
}
