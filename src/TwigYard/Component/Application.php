<?php

namespace TwigYard\Component;

use Relay\RelayBuilder;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use TwigYard\Middleware\Config\ConfigMiddleware;
use TwigYard\Middleware\Data\DataMiddleware;
use TwigYard\Middleware\Error\ErrorMiddleware;
use TwigYard\Middleware\Form\FormHandlerFactory;
use TwigYard\Middleware\Form\FormMiddleware;
use TwigYard\Middleware\Form\FormValidator;
use TwigYard\Middleware\Httpauth\HttpauthMiddleware;
use TwigYard\Middleware\Locale\LocaleMiddleware;
use TwigYard\Middleware\Redirect\RedirectMiddleware;
use TwigYard\Middleware\Renderer\RendererMiddleware;
use TwigYard\Middleware\Router\RouterMiddleware;
use TwigYard\Middleware\Tracking\TrackingMiddleware;
use TwigYard\Middleware\Url\UrlMiddleware;
use Zend\Diactoros\Response;
use Zend\Diactoros\Server;
use Zend\Diactoros\ServerRequestFactory;

class Application
{
    // Sites can not implement any other locales
    const VALID_LOCALES = ['cs_CZ', 'en_US', 'de_DE'];

    /**
     * @var ApplicationConfig
     */
    private $config;

    /**
     * @var string
     */
    private $appRoot;

    /**
     * @var ContainerBuilder
     */
    private $container;

    /**
     * @param string $appRoot
     * @param ApplicationConfig $config
     */
    public function __construct(string $appRoot, ApplicationConfig $config)
    {
        $this->appRoot = $appRoot;
        $this->config = $config;

        $containerFactory = new ContainerFactory($appRoot, $config);
        $this->container = $containerFactory->createContainer();
    }

    /**
     * @throws \Exception
     */
    public function run(): void
    {
        $server = new Server(
            (new RelayBuilder())->newInstance($this->getQueue()),
            ServerRequestFactory::fromGlobals($_SERVER, $_GET, $_POST, $_COOKIE, $_FILES),
            new Response()
        );
        $server->listen();
    }

    /**
     * @throws \Exception
     * @return array
     */
    private function getQueue(): array
    {
        $appState = new AppState($this->appRoot . '/' . $this->config->getSitesDir());

        $globalParameters = $this->container->getParameter('app.parameters');

        $validatorBuilderFactory = $this->container->get(ValidatorBuilderFactory::class);
        $mailerFactory = $this->container->get(MailerFactory::class);
        $twigTemplatingFactory = $this->container->get(TwigTemplatingFactory::class);
        $siteLoggerFactory = $this->container->get(SiteLoggerFactory::class);
        $loggerFactory = $this->container->get(LoggerFactory::class);
        $configCache = $this->container->get(ConfigCache::class);
        $curlDownloader = $this->container->get(CurlDownloader::class);
        $csrfTokenGenerator = $this->container->get(CsrfTokenGenerator::class);
        $httpRequestSender = $this->container->get(HttpRequestSender::class);
        $translatorFactory = $this->container->get(TranslatorFactory::class);
        $siteTranslatorFactory = $this->container->get(SiteTranslatorFactory::class);

        if (
            !$validatorBuilderFactory instanceof ValidatorBuilderFactory
            || !$mailerFactory instanceof MailerFactory
            || !$twigTemplatingFactory instanceof TwigTemplatingFactory
            || !$siteLoggerFactory instanceof SiteLoggerFactory
            || !$loggerFactory instanceof LoggerFactory
            || !$configCache instanceof ConfigCache
            || !$curlDownloader instanceof CurlDownloader
            || !$csrfTokenGenerator instanceof CsrfTokenGenerator
            || !$httpRequestSender instanceof HttpRequestSender
            || !$translatorFactory instanceof TranslatorFactory
            || !$siteTranslatorFactory instanceof SiteTranslatorFactory
        ) {
            throw new \Exception('Error while receiving instances from containers.');
        }

        $formValidator = new FormValidator($validatorBuilderFactory);
        $formHandlerFactory = new FormHandlerFactory(
            $appState,
            $mailerFactory,
            $twigTemplatingFactory,
            $siteLoggerFactory,
            $httpRequestSender
        );

        $queue = [];
        $queue[] = new ErrorMiddleware(
            $appState,
            $globalParameters['show_errors'],
            $loggerFactory,
            $this->config->getTemplateDir(),
            $this->config->getError404PageName(),
            $this->config->getError500PageName()
        );
        $queue[] = new ConfigMiddleware(
            $appState,
            $configCache,
            $this->appRoot . '/' . $this->config->getSitesDir(),
            $globalParameters['site_config'],
            $globalParameters['parent_domain']
        );
        $queue[] = new UrlMiddleware(
            $appState,
            !empty($globalParameters['ssl_allowed']),
            $globalParameters['parent_domain']
        );
        $queue[] = new RedirectMiddleware($appState);
        $queue[] = new HttpauthMiddleware($appState);
        $queue[] = new LocaleMiddleware($appState, self::VALID_LOCALES);
        $queue[] = new DataMiddleware(
            $appState,
            $this->config->getDataDir(),
            $curlDownloader
        );
        $queue[] = new RouterMiddleware($appState);
        $queue[] = new FormMiddleware(
            $appState,
            $csrfTokenGenerator,
            $formValidator,
            $formHandlerFactory,
            $translatorFactory,
            $siteTranslatorFactory,
            $this->config->getLogDir()
        );
        $queue[] = new TrackingMiddleware($appState, $globalParameters['tracking_enabled']);
        $queue[] = new RendererMiddleware(
            $appState,
            $twigTemplatingFactory
        );
        // $queue[] = new HeaderMiddleware($appState);

        return $queue;
    }
}
