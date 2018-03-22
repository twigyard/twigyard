<?php

namespace TwigYard\Component;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Yaml\Yaml;

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

        $containerBuilder->setParameter('app.root', $this->appRoot);
        $containerBuilder->setParameter('app.config', $this->appConfig);
        $containerBuilder->setParameter('app.parameters', $parameters);
        $containerBuilder->setParameter('app.site_parameters', $defaultSiteParameters);

        $containerBuilder->compile();

        return $containerBuilder;
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
                $this->appRoot . '/' .
                $this->appConfig->getConfigDir() . '/' .
                $this->appConfig->getDefaultSiteParameters()
            )
        )['parameters'];
    }
}
