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

    // Must not end with /
    const BASE_PATH = '';

    // path relative to application root
    const CONFIG_DIR = 'app/config';
    const GLOBAL_PARAMETERS = 'parameters.yml';
    const DEFAULT_SITE_PARAMETERS = 'default_site_parameters.yml';

    // path relative to application root
    const SITES_DIR = 'sites';

    // path relative to config cache directory
    const CONFIG_CACHE_DIR = 'var/cache';

    const LOG_DIR = 'var/log';


    // path relative to the site directory
    const ASSET_DIR = 'web';
    const DATA_DIR = 'src/data';
    const LANGUAGE_DIR = 'src/languages';
    const SITE_CACHE_DIR = 'var/cache';

    const SITE_PARAMETERS = 'parameters.yml';

    const TEMPLATE_DIR = 'src/templates';

    // can be located either in templates/ or templates/<locale> dirs
    const ERROR_404_PAGE_NAME = '404.html';
    const ERROR_500_PAGE_NAME = '500.html';

    // path relative to the ASSET_DIR
    const IMAGE_CACHE_DIR = 'image_cache';

    const CACHE_NAMESPACE_CONFIG = 'config';
    const CACHE_NAMESPACE_ASSETS = 'assets';


    /**
     * @var string
     */
    private $appRoot;

    /**
     * @var bool
     */
    private $localAccessAllowed;

    /**
     * @var bool
     */
    private $cacheEnabled;

    /**
     * @var bool
     */
    private $showErrors;

    /**
     * @var bool
     */
    private $enableTracking;

    /**
     * @var string
     */
    private $logOnLevel;

    /**
     * @var bool
     */
    private $debugEmailEnabled;

    /**
     * @param string $appRoot
     * @param bool $localAccessAllowed
     * @param bool $cacheEnabled
     * @param bool $showErrors
     * @param bool $enableTracking
     * @param string $logOnLevel
     * @param bool $debugEmailEnabled
     */
    public function __construct(
        $appRoot,
        $localAccessAllowed,
        $cacheEnabled,
        $showErrors,
        $enableTracking,
        $logOnLevel,
        $debugEmailEnabled
    ) {
        $this->appRoot = $appRoot;
        $this->localAccessAllowed = $localAccessAllowed;
        $this->cacheEnabled = $cacheEnabled;
        $this->showErrors = $showErrors;
        $this->enableTracking = $enableTracking;
        $this->logOnLevel = $logOnLevel;
        $this->debugEmailEnabled = $debugEmailEnabled;
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
        $tplFactory = $this->getTplFactory();
        $mailerFactory = $this->getMailerFactory($defaultSiteParameters);
        $csrfTokenGenerator = new CsrfTokenGenerator();
        $formValidator = new FormValidator($this->getValidatorBuilderFactory($appState));

        $formHandlerFactory = new FormHandlerFactory(
            $appState,
            $mailerFactory,
            $tplFactory,
            $this->getSiteLoggerFactory()
        );

        $globalLoggerFactory = $this->getGlobalLoggerFactory($globalParameters);

        $queue[] = new ErrorMiddleware(
            $appState,
            $this->showErrors,
            $globalLoggerFactory,
            self::TEMPLATE_DIR,
            self::ERROR_404_PAGE_NAME,
            self::ERROR_500_PAGE_NAME
        );
        $queue[] = new UrlMiddleware(
            $appState,
            $this->getConfigCache($this->cacheEnabled, $globalParameters),
            $this->appRoot . '/' . self::SITES_DIR,
            $globalParameters['site_config'],
            self::SITE_PARAMETERS,
            $this->localAccessAllowed,
            $globalParameters['parent_domain']
        );
        $queue[] = new RedirectMiddleware($appState);
        $queue[] = new HttpauthMiddleware($appState);
        $queue[] = new LocaleMiddleware($appState, self::VALID_LOCALES);
        $queue[] = new DataMiddleware($appState, self::DATA_DIR);
        $queue[] = new RouterMiddleware($appState);
        $queue[] = new FormMiddleware(
            $appState,
            $csrfTokenGenerator,
            $formValidator,
            $formHandlerFactory,
            $this->getTranslatorFactory($appState, $this->appRoot)
        );
        $queue[] = new TrackingMiddleware($appState, $this->enableTracking);
        $queue[] = new RendererMiddleware($appState, $tplFactory);

        return $queue;
    }

    /**
     * @return TwigTemplatingFactory
     */
    private function getTplFactory()
    {
        $imageFactory = new ImageFactory(self::BASE_PATH, self::IMAGE_CACHE_DIR);
        $assetCacheManagerFactory = new AssetCacheManagerFactory(self::BASE_PATH, self::CACHE_NAMESPACE_ASSETS);
        $tplClosureFactory = new TemplatingClosureFactory(self::BASE_PATH, $imageFactory, $assetCacheManagerFactory);

        return new TwigTemplatingFactory(
            self::TEMPLATE_DIR,
            self::LANGUAGE_DIR,
            self::ASSET_DIR,
            $tplClosureFactory,
            $this->cacheEnabled ? self::SITE_CACHE_DIR : null
        );
    }

    /**
     * @return array
     */
    private function getGlobalParameters()
    {
        return Yaml::parse(
            file_get_contents($this->appRoot . '/' . self::CONFIG_DIR . '/' . self::GLOBAL_PARAMETERS)
        )['parameters'];
    }

    /**
     * @return array
     */
    private function getDefaultSiteParameters()
    {
        return Yaml::parse(
            file_get_contents($this->appRoot . '/' . self::CONFIG_DIR . '/' . self::DEFAULT_SITE_PARAMETERS)
        )['parameters'];
    }

    /**
     * @param bool $cacheEnabled
     * @param array $parameters
     * @return \TwigYard\Component\ConfigCache
     */
    private function getConfigCache($cacheEnabled, array $parameters)
    {
        $cacheStorage = $cacheEnabled
            ? new FileStorage($this->appRoot . '/' . self::CONFIG_CACHE_DIR)
            : new DevNullStorage();
        $cache = new Cache($cacheStorage, self::CACHE_NAMESPACE_CONFIG);

        return new ConfigCache($cache, $this->getGlobalLoggerFactory($parameters));
    }

    /**
     * @param array $globalParameters
     * @return \TwigYard\Component\LoggerFactory
     */
    private function getGlobalLoggerFactory(array $globalParameters)
    {
        return new LoggerFactory(
            $this->appRoot . '/' . self::LOG_DIR,
            $this->logOnLevel,
            $globalParameters['log_rotation_enabled'],
            (isset($globalParameters['log_max_files']) ? $globalParameters['log_max_files'] : null),
            $globalParameters['loggly_token'],
            $globalParameters['loggly_tags']
        );
    }

    /**
     * @return \TwigYard\Component\SiteLoggerFactory
     */
    private function getSiteLoggerFactory()
    {
        return new SiteLoggerFactory(self::LOG_DIR, $this->logOnLevel);
    }

    /**
     * @param array $defaultSiteParameters
     * @return \TwigYard\Component\MailerFactory
     */
    private function getMailerFactory(array $defaultSiteParameters)
    {
        return new MailerFactory($defaultSiteParameters, $this->debugEmailEnabled);
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
        return new TranslatorFactory($appState, $appRoot, self::SITE_CACHE_DIR);
    }
}
