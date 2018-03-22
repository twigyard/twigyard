<?php

namespace TwigYard\Component;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
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
use Relay\RelayBuilder;
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
    public function __construct($appRoot, ApplicationConfig $config)
    {
        $this->appRoot = $appRoot;
        $this->config = $config;

        $containerFactory = new ContainerFactory($appRoot, $config);
        $this->container = $containerFactory->createContainer();
    }

    public function run()
    {
        $server = new Server(
            (new RelayBuilder())->newInstance($this->getQueue()),
            ServerRequestFactory::fromGlobals($_SERVER, $_GET, $_POST, $_COOKIE, $_FILES),
            new Response()
        );
        $server->listen();
    }

    /**
     * @return Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @return array
     */
    private function getQueue()
    {
        $appState = new AppState();

        $globalParameters = $this->container->getParameter('app.parameters');

        $formValidator = new FormValidator($this->container->get(ValidatorBuilderFactory::class));
        $formHandlerFactory = new FormHandlerFactory(
            $appState,
            $this->container->get(MailerFactory::class),
            $this->container->get(TwigTemplatingFactory::class),
            $this->container->get(SiteLoggerFactory::class)
        );

        $queue[] = new ErrorMiddleware(
            $appState,
            $globalParameters['show_errors'],
            $this->container->get(LoggerFactory::class),
            $this->config->getTemplateDir(),
            $this->config->getError404PageName(),
            $this->config->getError500PageName()
        );
        $queue[] = new UrlMiddleware(
            $appState,
            $this->container->get(ConfigCache::class),
            $this->appRoot . '/' . $this->config->getSitesDir(),
            $globalParameters['site_config'],
            $this->config->getSiteParameters(),
            $globalParameters['parent_domain'],
            !empty($globalParameters['ssl_allowed'])
        );
        $queue[] = new RedirectMiddleware($appState);
        $queue[] = new HttpauthMiddleware($appState);
        $queue[] = new LocaleMiddleware($appState, self::VALID_LOCALES);
        $queue[] = new DataMiddleware(
            $appState,
            $this->config->getDataDir(),
            $this->container->get(CurlDownloader::class)
        );
        $queue[] = new RouterMiddleware($appState);
        $queue[] = new FormMiddleware(
            $appState,
            $this->container->get(CsrfTokenGenerator::class),
            $formValidator,
            $formHandlerFactory,
            $this->container->get(TranslatorFactory::class),
            $this->container->get(SiteTranslatorFactory::class),
            $this->config->getLogDir()
        );
        $queue[] = new TrackingMiddleware($appState, $globalParameters['tracking_enabled']);
        $queue[] = new RendererMiddleware(
            $appState,
            $this->container->get(TwigTemplatingFactory::class)
        );

        return $queue;
    }
}
