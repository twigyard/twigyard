<?php

namespace TwigYard\Component;

use Nette\Caching\Cache;
use Symfony\Component\Config\Exception\LoaderLoadException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\Yaml\Exception\ParseException;
use TwigYard\Exception\InvalidSiteConfigException;

class ConfigCache implements ConfigCacheInterface
{
    const LOGGER_CHANNEL = 'config_cache';

    /**
     * @var Cache
     */
    private $cache;

    /**
     * @var LoggerFactory
     */
    private $loggerFactory;

    /**
     * @var string
     */
    private $sitesDir;

    /**
     * @var string
     */
    private $siteConfig;

    /**
     * ConfigCache constructor.
     */
    public function __construct(Cache $cache, LoggerFactory $loggerFactory, string $sitesDir, string $siteConfig)
    {
        $this->cache = $cache;
        $this->loggerFactory = $loggerFactory;
        $this->sitesDir = $sitesDir;
        $this->siteConfig = $siteConfig;
    }

    /**
     * @throws \Exception
     */
    public function getConfig(): array
    {
        $logger = $this->loggerFactory->getLogger(self::LOGGER_CHANNEL);

        return $this->cache->load($this->sitesDir, function () use ($logger) {
            $configs = [];
            $siteDirs = scandir($this->sitesDir);
            if (!$siteDirs) {
                return $configs;
            }

            foreach ($siteDirs as $dirName) {
                $dirPath = $this->sitesDir . '/' . $dirName;
                if (is_dir($dirPath) && $dirName !== '.' && $dirName !== '..') {
                    try {
                        $config = (new YamlConfigFileLoader(new FileLocator($dirPath)))->load($this->siteConfig);
                    } catch (LoaderLoadException $e) {
                        $logger->error($e->getMessage());
                        continue;
                    } catch (ParseException $e) {
                        $logger->error($e->getMessage());
                        continue;
                    } catch (InvalidSiteConfigException $e) {
                        $logger->error($e->getMessage());
                        continue;
                    } catch (\InvalidArgumentException $e) {
                        $logger->error($e->getMessage());
                        continue;
                    }

                    if (isset($config['parameters'])) {
                        try {
                            $paramBag = new ParameterBag($config['parameters']);
                            $config = $paramBag->resolveValue($config);
                        } catch (ParameterNotFoundException $e) {
                            $logger->error($e->getMessage());
                            continue;
                        }
                    }

                    if (!isset($config['version']) || $config['version'] === 1) {
                        if (!isset($config['url'])) {
                            continue;
                        }
                        $urlConf = $config['url'];
                        $config['version'] = 1;
                    } elseif ($config['version'] === 2) {
                        if (!isset($config['middlewares']['url'])) {
                            continue;
                        }
                        $urlConf = $config['middlewares']['url'];
                    }

                    if (!isset($urlConf['canonical']) || isset($configs[$urlConf['canonical']])) {
                        continue;
                    }

                    $backupConfigs = $configs;
                    $configs[$urlConf['canonical']] = $config;
                    if (isset($urlConf['extra'])) {
                        foreach ($urlConf['extra'] as $url) {
                            if (isset($configs[$url])) {
                                $configs = $backupConfigs;
                                continue 2;
                            }
                            $configs[$url] = &$configs[$urlConf['canonical']];
                        }
                    }
                }
            }

            return $configs;
        });
    }
}
