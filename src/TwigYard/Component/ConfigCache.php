<?php

namespace TwigYard\Component;

use Nette\Caching\Cache;
use Symfony\Component\Config\Exception\FileLoaderLoadException;
use Symfony\Component\Config\FileLocator;
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
     * ConfigCache constructor.
     * @param Cache $cache
     * @param LoggerFactory $loggerFactory
     */
    public function __construct(Cache $cache, LoggerFactory $loggerFactory)
    {
        $this->cache = $cache;
        $this->loggerFactory = $loggerFactory;
    }

    /**
     * @param string $sitesDir
     * @param string $siteConfig
     * @return array
     */
    public function getConfig(string $sitesDir, string $siteConfig): array
    {
        $logger = $this->loggerFactory->getLogger(self::LOGGER_CHANNEL);

        return $this->cache->load($sitesDir, function () use ($sitesDir, $siteConfig, $logger) {
            $siteDirs = scandir($sitesDir);
            $configs = [];
            foreach ($siteDirs as $dirName) {
                $dirPath = $sitesDir . '/' . $dirName;
                if (is_dir($dirPath) && $dirName !== '.' && $dirName !== '..') {
                    try {
                        $config = (new YamlConfigFileLoader(new FileLocator($dirPath)))->load($siteConfig);
                    } catch (FileLoaderLoadException $e) {
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
                    if (!isset($config['url']['canonical']) || isset($configs[$config['url']['canonical']])) {
                        continue;
                    }
                    $backupConfigs = $configs;
                    $configs[$config['url']['canonical']] = $config;
                    if (isset($config['url']['extra'])) {
                        foreach ($config['url']['extra'] as $url) {
                            if (isset($configs[$url])) {
                                $configs = $backupConfigs;
                                continue 2;
                            }
                            $configs[$url] = &$configs[$config['url']['canonical']];
                        }
                    }
                }
            }

            return $configs;
        });
    }
}
