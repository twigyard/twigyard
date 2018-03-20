<?php

namespace TwigYard\Component;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use TwigYard\Exception\InvalidApplicationConfigException;
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
use Nette\Caching\Cache;
use Nette\Caching\Storages\DevNullStorage;
use Nette\Caching\Storages\FileStorage;
use Relay\RelayBuilder;
use Symfony\Component\Yaml\Yaml;
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
        $this->container = $this->createContainer();
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
     * @return ContainerBuilder
     */
    public function createContainer()
    {
        $containerBuilder = new ContainerBuilder();
        $loader = new YamlFileLoader($containerBuilder, new FileLocator(__DIR__));
        $loader->load('../../../config/services.yml');

        $this->registerConfigCacheService($containerBuilder, $this->getGlobalParameters());
        $this->registerLoggerFactoryService($containerBuilder, $this->getGlobalParameters());

        $containerBuilder->setParameter('app.root', $this->appRoot);
        $containerBuilder->setParameter('app.config', $this->config);
        $containerBuilder->setParameter('app.parameters', $this->getGlobalParameters());
        $containerBuilder->setParameter('app.site_parameters', $this->getDefaultSiteParameters());

        $containerBuilder->compile();

        return $containerBuilder;
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

        $container = $this->container;
        $globalParameters = $this->getGlobalParameters();

        $formValidator = new FormValidator($container->get(ValidatorBuilderFactory::class));
        $formHandlerFactory = new FormHandlerFactory(
            $appState,
            $container->get(MailerFactory::class),
            $container->get(TwigTemplatingFactory::class),
            $container->get(SiteLoggerFactory::class)
        );

        $queue[] = new ErrorMiddleware(
            $appState,
            $globalParameters['show_errors'],
            $container->get(LoggerFactory::class),
            $this->config->getTemplateDir(),
            $this->config->getError404PageName(),
            $this->config->getError500PageName()
        );
        $queue[] = new UrlMiddleware(
            $appState,
            $container->get(ConfigCache::class),
            $this->appRoot . '/' . $this->config->getSitesDir(),
            $globalParameters['site_config'],
            $this->config->getSiteParameters(),
            $globalParameters['parent_domain'],
            !empty($globalParameters['ssl_allowed'])
        );
        $queue[] = new RedirectMiddleware($appState);
        $queue[] = new HttpauthMiddleware($appState);
        $queue[] = new LocaleMiddleware($appState, self::VALID_LOCALES);
        $queue[] = new DataMiddleware($appState, $this->config->getDataDir(), $container->get(CurlDownloader::class));
        $queue[] = new RouterMiddleware($appState);
        $queue[] = new FormMiddleware(
            $appState,
            $container->get(CsrfTokenGenerator::class),
            $formValidator,
            $formHandlerFactory,
            $container->get(TranslatorFactory::class),
            $container->get(SiteTranslatorFactory::class),
            $this->config->getLogDir()
        );
        $queue[] = new TrackingMiddleware($appState, $globalParameters['tracking_enabled']);
        $queue[] = new RendererMiddleware($appState, $container->get(TwigTemplatingFactory::class));

        return $queue;
    }

    /**
     * @return array
     */
    private function getGlobalParameters()
    {
        return Yaml::parse(
            file_get_contents(
                $this->appRoot . '/' . $this->config->getConfigDir() . '/' . $this->config->getGlobalParameters()
            )
        )['parameters'];
    }

    /**
     * @return array
     */
    private function getDefaultSiteParameters()
    {
        return Yaml::parse(
            file_get_contents(
                $this->appRoot . '/' . $this->config->getConfigDir() . '/' . $this->config->getDefaultSiteParameters()
            )
        )['parameters'];
    }

    /**
     * @param ContainerBuilder $containerBuilder
     * @param array $parameters
     * @return ConfigCache
     */
    private function registerConfigCacheService(ContainerBuilder $containerBuilder, array $parameters)
    {
        $cacheStorage = $parameters['cache_enabled']
            ? new FileStorage($this->appRoot . '/' . $this->config->getConfigCacheDir())
            : new DevNullStorage();
        $cache = new Cache($cacheStorage, $this->config->getCacheNamespaceConfig());

        $containerBuilder->register(ConfigCache::class)
            ->setPublic(true)
            ->addArgument($cache)
            ->addArgument(new Reference(LoggerFactory::class));
    }

    /**
     * @param ContainerBuilder $containerBuilder
     * @param array $globalParameters
     * @throws InvalidApplicationConfigException
     */
    private function registerLoggerFactoryService(ContainerBuilder $containerBuilder, array $globalParameters)
    {
        if (isset($globalParameters['log_rotation_enabled']) && !isset($globalParameters['log_max_files'])) {
            throw new InvalidApplicationConfigException(
                'If there is log_rotation_enabled defined in the configuration, log_max_files has to be defined too.'
            );
        }

        if (isset($globalParameters['log_max_files']) && !isset($globalParameters['log_rotation_enabled'])) {
            throw new InvalidApplicationConfigException(
                'If there is log_max_files defined in the configuration, log_rotation_enabled has to be defined too.'
            );
        }

        if (isset($globalParameters['loggly_token']) && !isset($globalParameters['loggly_tags'])) {
            throw new InvalidApplicationConfigException(
                'If there is loggly_token defined in the configuration, loggly_tags has to be defined too.'
            );
        }

        if (isset($globalParameters['loggly_tags']) && !isset($globalParameters['loggly_token'])) {
            throw new InvalidApplicationConfigException(
                'If there is loggly_tags defined in the configuration, loggly_token has to be defined too.'
            );
        }

        $containerBuilder->register(LoggerFactory::class)
            ->setPublic(true)
            ->addArgument($this->appRoot . '/' . $this->config->getLogDir())
            ->addArgument(constant('Monolog\Logger::' . strtoupper($globalParameters['log_on_level'])))
            ->addArgument(
                isset($globalParameters['log_rotation_enabled']) ? $globalParameters['log_rotation_enabled'] : false
            )
            ->addArgument(isset($globalParameters['log_max_files']) ? $globalParameters['log_max_files'] : null)
            ->addArgument(isset($globalParameters['loggly_token']) ? $globalParameters['loggly_token'] : null)
            ->addArgument(isset($globalParameters['loggly_tags']) ? $globalParameters['loggly_tags'] : []);
    }
}
