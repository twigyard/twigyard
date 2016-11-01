<?php

namespace TwigYard\Component;

use Monolog\Logger;
use TwigYard\Exception\InvalidApplicationConfigException;

class ApplicationConfig
{
    const TYPE_STRING = 'string';
    const TYPE_ARRAY = 'array';
    
    /**
     * @var string
     */
    private $basePath;

    /**
     * @var string
     */
    private $configDir;

    /**
     * @var string
     */
    private $globalParameters;

    /**
     * @var string
     */
    private $defaultSiteParameters;

    /**
     * @var string
     */
    private $sitesDir;

    /**
     * @var string
     */
    private $configCacheDir;

    /**
     * @var string
     */
    private $logDir;

    /**
     * @var string
     */
    private $assetDir;

    /**
     * @var string
     */
    private $dataDir;

    /**
     * @var string
     */
    private $languageDir;

    /**
     * @var string
     */
    private $siteCacheDir;

    /**
     * @var string
     */
    private $templateDir;

    /**
     * @var string
     */
    private $siteParameters;

    /**
     * @var string
     */
    private $error404PageName;

    /**
     * @var string
     */
    private $error500PageName;

    /**
     * @var string
     */
    private $imageCacheDir;

    /**
     * @var string
     */
    private $cacheNamespaceConfig;

    /**
     * @var string
     */
    private $cacheNamespaceAssets;

    /**
     * ApplicationConfig constructor.
     * @param array $config
     * @throws InvalidApplicationConfigException
     */
    public function __construct(array $config)
    {
        $mandatoryConfigKeys = [
            'base_path' => self::TYPE_STRING,
            'config_dir' => self::TYPE_STRING,
            'global_parameters' => self::TYPE_STRING,
            'default_site_parameters' => self::TYPE_STRING,
            'sites_dir' => self::TYPE_STRING,
            'config_cache_dir' => self::TYPE_STRING,
            'log_dir' => self::TYPE_STRING,
            'asset_dir' => self::TYPE_STRING,
            'data_dir' => self::TYPE_STRING,
            'language_dir' => self::TYPE_STRING,
            'site_cache_dir' => self::TYPE_STRING,
            'template_dir' => self::TYPE_STRING,
            'site_parameters' => self::TYPE_STRING,
            'error_404_page_name' => self::TYPE_STRING,
            'error_500_page_name' => self::TYPE_STRING,
            'image_cache_dir' => self::TYPE_STRING,
            'cache_namespace_config' => self::TYPE_STRING,
            'cache_namespace_assets' => self::TYPE_STRING,
            ];

        foreach ($mandatoryConfigKeys as $configKey => $keyType) {
            if (!array_key_exists($configKey, $config)) {
                throw new InvalidApplicationConfigException(sprintf('The property %s is mandatory.', $configKey));
            }
            
            switch ($keyType) {
                case self::TYPE_ARRAY:
                    $this->setArray($configKey, $config[$configKey]);
                    break;
                
                case self::TYPE_STRING:
                default:
                    $this->setString($configKey, $config[$configKey]);
                    break;
            }
        }
    }

    /**
     * @return string
     */
    public function getBasePath()
    {
        return $this->basePath;
    }

    /**
     * @return string
     */
    public function getConfigDir()
    {
        return $this->configDir;
    }

    /**
     * @return string
     */
    public function getGlobalParameters()
    {
        return $this->globalParameters;
    }

    /**
     * @return array
     */
    public function getDefaultSiteParameters()
    {
        return $this->defaultSiteParameters;
    }

    /**
     * @return string
     */
    public function getSitesDir()
    {
        return $this->sitesDir;
    }

    /**
     * @return string
     */
    public function getConfigCacheDir()
    {
        return $this->configCacheDir;
    }

    /**
     * @return string
     */
    public function getLogDir()
    {
        return $this->logDir;
    }

    /**
     * @return string
     */
    public function getAssetDir()
    {
        return $this->assetDir;
    }

    /**
     * @return string
     */
    public function getDataDir()
    {
        return $this->dataDir;
    }

    /**
     * @return string
     */
    public function getLanguageDir()
    {
        return $this->languageDir;
    }

    /**
     * @return string
     */
    public function getSiteCacheDir()
    {
        return $this->siteCacheDir;
    }

    /**
     * @return string
     */
    public function getTemplateDir()
    {
        return $this->templateDir;
    }

    /**
     * @return string
     */
    public function getSiteParameters()
    {
        return $this->siteParameters;
    }

    /**
     * @return string
     */
    public function getError404PageName()
    {
        return $this->error404PageName;
    }

    /**
     * @return string
     */
    public function getError500PageName()
    {
        return $this->error500PageName;
    }

    /**
     * @return string
     */
    public function getImageCacheDir()
    {
        return $this->imageCacheDir;
    }

    /**
     * @return string
     */
    public function getCacheNamespaceConfig()
    {
        return $this->cacheNamespaceConfig;
    }

    /**
     * @return string
     */
    public function getCacheNamespaceAssets()
    {
        return $this->cacheNamespaceAssets;
    }

    /**
     * @param string $index
     * @return string
     */
    private function convertIndexToProperty($index)
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $index))));
    }

    /**
     * @param string $index
     * @param array $value
     */
    private function setArray($index, array $value)
    {
        $this->{$this->convertIndexToProperty($index)} = $value;
    }

    /**
     * @param string $index
     * @param string $value
     */
    private function setString($index, $value)
    {
        $this->{$this->convertIndexToProperty($index)} = (string) $value;
    }
}
