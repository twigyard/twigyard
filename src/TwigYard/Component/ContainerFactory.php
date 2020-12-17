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
     */
    public function __construct(string $appRoot, ApplicationConfig $appConfig)
    {
        $this->appRoot = $appRoot;
        $this->appConfig = $appConfig;
    }

    /**
     * @throws \Exception
     */
    public function createContainer(): ContainerBuilder
    {
        $parameters = $this->getGlobalParameters();

        $containerBuilder = new ContainerBuilder();
        $loader = new YamlFileLoader($containerBuilder, new FileLocator(__DIR__));
        $loader->load('../../../config/services.yml');

        $containerBuilder->setParameter('app.root', $this->appRoot);
        $containerBuilder->setParameter('app.config', $this->appConfig);
        $containerBuilder->setParameter('app.parameters', $parameters);

        $containerBuilder->compile();

        return $containerBuilder;
    }

    private function getGlobalParameters(): array
    {
        $fileContents = file_get_contents(
            $this->appRoot . '/' . $this->appConfig->getConfigDir() . '/' . $this->appConfig->getGlobalParameters()
        );

        return $fileContents ? Yaml::parse($fileContents)['parameters'] : [];
    }
}
