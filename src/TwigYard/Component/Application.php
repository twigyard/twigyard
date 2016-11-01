<?php

namespace TwigYard\Component;

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
    const VALID_LOCALES = ['cs_CZ', 'en_US'];

    /**
     * @var ApplicationConfig
     */
    private $config;

    /**
     * @var string
     */
    private $appRoot;

    /**
     * @param string $appRoot
     * @param ApplicationConfig $config
     */
    public function __construct($appRoot, ApplicationConfig $config)
    {
        $this->appRoot = $appRoot;
        $this->config = $config;
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
     * @return array
     */
    private function getQueue()
    {
        $appState = new AppState();
        $globalParameters = $this->getGlobalParameters();
        $defaultSiteParameters = $this->getDefaultSiteParameters();
        $tplFactory = $this->getTplFactory($globalParameters);
        $mailerFactory = $this->getMailerFactory($defaultSiteParameters);
        $csrfTokenGenerator = new CsrfTokenGenerator();
        $formValidator = new FormValidator($this->getValidatorBuilderFactory($appState));

        $formHandlerFactory = new FormHandlerFactory(
            $appState,
            $mailerFactory,
            $tplFactory,
            $this->getSiteLoggerFactory($globalParameters)
        );

        $globalLoggerFactory = $this->getGlobalLoggerFactory($globalParameters);

        $queue[] = new ErrorMiddleware(
            $appState,
            $globalParameters['show_errors'],
            $globalLoggerFactory,
            $this->config->getTemplateDir(),
            $this->config->getError404PageName(),
            $this->config->getError500PageName()
        );
        $queue[] = new UrlMiddleware(
            $appState,
            $this->getConfigCache($globalParameters),
            $this->appRoot . '/' . $this->config->getSitesDir(),
            $globalParameters['site_config'],
            $this->config->getSiteParameters(),
            $globalParameters['parent_domain']
        );
        $queue[] = new RedirectMiddleware($appState);
        $queue[] = new HttpauthMiddleware($appState);
        $queue[] = new LocaleMiddleware($appState, self::VALID_LOCALES);
        $queue[] = new DataMiddleware($appState, $this->config->getDataDir());
        $queue[] = new RouterMiddleware($appState);
        $queue[] = new FormMiddleware(
            $appState,
            $csrfTokenGenerator,
            $formValidator,
            $formHandlerFactory,
            $this->getTranslatorFactory($appState, $this->appRoot),
            $this->config->getLogDir()
        );
        $queue[] = new TrackingMiddleware($appState, $globalParameters['tracking_enabled']);
        $queue[] = new RendererMiddleware($appState, $tplFactory);

        return $queue;
    }

    /**
     * @param array $globalParameters
     * @return TwigTemplatingFactory
     */
    private function getTplFactory(array $globalParameters)
    {
        $imageFactory = new ImageFactory($this->config->getBasePath(), $this->config->getImageCacheDir());
        $assetCacheManagerFactory = new AssetCacheManagerFactory(
            $this->config->getBasePath(),
            $this->config->getCacheNamespaceAssets()
        );
        $tplClosureFactory = new TemplatingClosureFactory(
            $this->config->getBasePath(),
            $imageFactory,
            $assetCacheManagerFactory
        );

        return new TwigTemplatingFactory(
            $this->config->getTemplateDir(),
            $this->config->getLanguageDir(),
            $this->config->getAssetDir(),
            $tplClosureFactory,
            $globalParameters['cache_enabled'] ? $this->config->getSiteCacheDir() : null
        );
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
     * @param array $parameters
     * @return \TwigYard\Component\ConfigCache
     */
    private function getConfigCache(array $parameters)
    {
        $cacheStorage = $parameters['cache_enabled']
            ? new FileStorage($this->appRoot . '/' . $this->config->getConfigCacheDir())
            : new DevNullStorage();
        $cache = new Cache($cacheStorage, $this->config->getCacheNamespaceConfig());

        return new ConfigCache($cache, $this->getGlobalLoggerFactory($parameters));
    }

    /**
     * @param array $globalParameters
     * @return \TwigYard\Component\LoggerFactory
     */
    private function getGlobalLoggerFactory(array $globalParameters)
    {
        return new LoggerFactory(
            $this->appRoot . '/' . $this->config->getLogDir(),
            constant('Monolog\Logger::' . strtoupper($globalParameters['log_on_level'])),
            $globalParameters['log_rotation_enabled'],
            (isset($globalParameters['log_max_files']) ? $globalParameters['log_max_files'] : null),
            $globalParameters['loggly_token'],
            $globalParameters['loggly_tags']
        );
    }

    /**
     * @param array $globalParameters
     * @return \TwigYard\Component\SiteLoggerFactory
     */
    private function getSiteLoggerFactory(array $globalParameters)
    {
        return new SiteLoggerFactory($this->config->getLogDir(), $globalParameters['log_on_level']);
    }

    /**
     * @param array $defaultSiteParameters
     * @return \TwigYard\Component\MailerFactory
     */
    private function getMailerFactory(array $defaultSiteParameters)
    {
        return new MailerFactory($defaultSiteParameters);
    }

    /**
     * @param \TwigYard\Component\AppState $appState
     * @return \TwigYard\Component\ValidatorBuilderFactory
     */
    private function getValidatorBuilderFactory(AppState $appState)
    {
        return new ValidatorBuilderFactory($appState);
    }

    /**
     * @param \TwigYard\Component\AppState $appState
     * @param string $appRoot
     * @return \TwigYard\Component\TranslatorFactory
     */
    private function getTranslatorFactory(AppState $appState, $appRoot)
    {
        return new TranslatorFactory($appState, $appRoot, $this->config->getSiteCacheDir());
    }
}
