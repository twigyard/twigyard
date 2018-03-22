<?php

namespace TwigYard\Component;


use Nette\Caching\Cache;
use Nette\Caching\Storages\DevNullStorage;
use Nette\Caching\Storages\FileStorage;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Yaml\Yaml;
use TwigYard\Exception\InvalidApplicationConfigException;


class ContainerFactory
{
    /**
     * @var string
     */
    private $appRoot;

    /**
     * @var ApplicationConfig
     */
    private $appConfig;

    /**
     * ContainerFactory constructor.
     * @param $appRoot
     * @param ApplicationConfig $appConfig
     */
    public function __construct($appRoot, ApplicationConfig $appConfig)
    {
        $this->appRoot = $appRoot;
        $this->appConfig = $appConfig;
    }

    /**
     * @return ContainerBuilder
     */
    public function createContainer()
    {
        $parameters = $this->getGlobalParameters();
        $defaultSiteParameters = $this->getDefaultSiteParameters();

        $containerBuilder = new ContainerBuilder();
        $loader = new YamlFileLoader($containerBuilder, new FileLocator(__DIR__));
        $loader->load('../../../config/services.yml');

        $this->registerConfigCacheService($containerBuilder, $parameters);
        $this->registerLoggerFactoryService($containerBuilder, $parameters);

        $containerBuilder->setParameter('app.root', $this->appRoot);
        $containerBuilder->setParameter('app.config', $this->appConfig);
        $containerBuilder->setParameter('app.parameters', $parameters);
        $containerBuilder->setParameter('app.site_parameters', $defaultSiteParameters);

        $containerBuilder->compile();

        return $containerBuilder;
    }

    /**
     * @param ContainerBuilder $containerBuilder
     * @param array $parameters
     * @return ConfigCache
     */
    private function registerConfigCacheService(ContainerBuilder $containerBuilder, array $parameters)
    {
        $cacheStorage = $parameters['cache_enabled']
            ? new FileStorage($this->appRoot . '/' . $this->appConfig->getConfigCacheDir())
            : new DevNullStorage();
        $cache = new Cache($cacheStorage, $this->appConfig->getCacheNamespaceConfig());

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
            ->addArgument($this->appRoot . '/' . $this->appConfig->getLogDir())
            ->addArgument(constant('Monolog\Logger::' . strtoupper($globalParameters['log_on_level'])))
            ->addArgument(
                isset($globalParameters['log_rotation_enabled']) ? $globalParameters['log_rotation_enabled'] : false
            )
            ->addArgument(isset($globalParameters['log_max_files']) ? $globalParameters['log_max_files'] : null)
            ->addArgument(isset($globalParameters['loggly_token']) ? $globalParameters['loggly_token'] : null)
            ->addArgument(isset($globalParameters['loggly_tags']) ? $globalParameters['loggly_tags'] : []);
    }


    /**
     * @return array
     */
    private function getGlobalParameters()
    {
        return Yaml::parse(
            file_get_contents(
                $this->appRoot . '/' . $this->appConfig->getConfigDir() . '/' . $this->appConfig->getGlobalParameters()
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
                $this->appRoot . '/' . $this->appConfig->getConfigDir() . '/' . $this->appConfig->getDefaultSiteParameters()
            )
        )['parameters'];
    }
}