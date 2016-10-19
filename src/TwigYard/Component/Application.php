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
     * @var
     */
    private $config;

    /**
     * @var string
     */
    private $appRoot;

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
     * @param bool $cacheEnabled
     * @param bool $showErrors
     * @param bool $enableTracking
     * @param string $logOnLevel
     * @param bool $debugEmailEnabled
     * @param array $config
     */
    public function __construct(
        $appRoot,
        $cacheEnabled,
        $showErrors,
        $enableTracking,
        $logOnLevel,
        $debugEmailEnabled,
        $config
    ) {
        $this->appRoot = $appRoot;
        $this->cacheEnabled = $cacheEnabled;
        $this->showErrors = $showErrors;
        $this->enableTracking = $enableTracking;
        $this->logOnLevel = $logOnLevel;
        $this->debugEmailEnabled = $debugEmailEnabled;
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
            $this->config['template_dir'],
            $this->config['error_404_page_name'],
            $this->config['error_500_page_name']
        );
        $queue[] = new UrlMiddleware(
            $appState,
            $this->getConfigCache($this->cacheEnabled, $globalParameters),
            $this->appRoot . '/' . $this->config['sites_dir'],
            $globalParameters['site_config'],
            $this->config['site_parameters'],
            $globalParameters['parent_domain']
        );
        $queue[] = new RedirectMiddleware($appState);
        $queue[] = new HttpauthMiddleware($appState);
        $queue[] = new LocaleMiddleware($appState, self::VALID_LOCALES);
        $queue[] = new DataMiddleware($appState, $this->config['data_dir']);
        $queue[] = new RouterMiddleware($appState);
        $queue[] = new FormMiddleware(
            $appState,
            $csrfTokenGenerator,
            $formValidator,
            $formHandlerFactory,
            $this->getTranslatorFactory($appState, $this->appRoot),
            $this->config['log_dir']
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
        $imageFactory = new ImageFactory($this->config['base_path'], $this->config['image_cache_dir']);
        $assetCacheManagerFactory = new AssetCacheManagerFactory(
            $this->config['base_path'],
            $this->config['cache_namespace_assets']
        );
        $tplClosureFactory = new TemplatingClosureFactory(
            $this->config['base_path'],
            $imageFactory,
            $assetCacheManagerFactory
        );

        return new TwigTemplatingFactory(
            $this->config['template_dir'],
            $this->config['language_dir'],
            $this->config['asset_dir'],
            $tplClosureFactory,
            $this->cacheEnabled ? $this->config['site_cache_dir'] : null
        );
    }

    /**
     * @return array
     */
    private function getGlobalParameters()
    {
        return Yaml::parse(
            file_get_contents(
                $this->appRoot . '/' . $this->config['config_dir'] . '/' . $this->config['global_parameters']
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
                $this->appRoot . '/' . $this->config['config_dir'] . '/' . $this->config['default_site_parameters']
            )
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
            ? new FileStorage($this->appRoot . '/' . $this->config['config_cache_dir'])
            : new DevNullStorage();
        $cache = new Cache($cacheStorage, $this->config['cache_namespace_config']);

        return new ConfigCache($cache, $this->getGlobalLoggerFactory($parameters));
    }

    /**
     * @param array $globalParameters
     * @return \TwigYard\Component\LoggerFactory
     */
    private function getGlobalLoggerFactory(array $globalParameters)
    {
        return new LoggerFactory(
            $this->appRoot . '/' . $this->config['log_dir'],
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
        return new SiteLoggerFactory($this->config['log_dir'], $this->logOnLevel);
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
        return new TranslatorFactory($appState, $appRoot, $this->config['site_cache_dir']);
    }
}
