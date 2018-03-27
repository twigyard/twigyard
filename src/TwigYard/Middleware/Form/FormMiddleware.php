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
     * @var string
     */
    private $siteCacheDir;

    /**
     * @var string
     */
    private $languageResourcesDir;

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
        $logDir
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
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param callable $next
     * @throws LogDirectoryNotWritableException
     * @throws InvalidFormNameException
     * @return \Dflydev\FigCookies\SetCookie|\Psr\Http\Message\ResponseInterface
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
                            $handler->handle($formData);
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
     * @param \Symfony\Component\Translation\Translator $translator
     * @return ResponseInterface
     */
    private function getSubmitSuccessResponse($formName, array $formConf, $path, Translator $translator)
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
     * @return \Dflydev\FigCookies\SetCookie
     */
    private function getCsrfCookie($csrfToken)
    {
        return SetCookie::create(self::CSRF_COOKIE_NAME)
            ->withValue($csrfToken)
            ->withExpires((new \DateTime())->modify(sprintf('+%d seconds', self::CSRF_COOKIE_TTL)));
    }

    /**
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param string $flashMessage
     * @param string $flashMessageType
     * @return ResponseInterface
     */
    private function setFlashMessageCookie(ResponseInterface $response, $flashMessage = null, $flashMessageType = null)
    {
        $cookie = SetCookie::create(self::FLASH_MESSAGE_COOKIE_NAME)
            ->withValue($flashMessage)
            ->withExpires((new \DateTime())->modify(sprintf('+%d seconds', self::FLASH_MESSAGE_COOKIE_TTL)));

        $typeCookie = SetCookie::create(self::FLASH_MESSAGE_TYPE_COOKIE_NAME)
            ->withValue($flashMessageType)
            ->withExpires((new \DateTime())->modify(sprintf('+%d seconds', self::FLASH_MESSAGE_COOKIE_TTL)));

        $modifiedResponse = FigResponseCookies::set($response, $cookie);
        $modifiedResponse = FigResponseCookies::set($modifiedResponse, $typeCookie);

        return $modifiedResponse;
    }
}
