<?php

namespace TwigYard\Component;

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
     * @throws InvalidApplicationConfigException
     */
    public function __construct(array $config)
    {
        $mandatoryConfigKeys = [
            'base_path' => self::TYPE_STRING,
            'config_dir' => self::TYPE_STRING,
            'global_parameters' => self::TYPE_STRING,
            'sites_dir' => self::TYPE_STRING,
            'config_cache_dir' => self::TYPE_STRING,
            'log_dir' => self::TYPE_STRING,
            'asset_dir' => self::TYPE_STRING,
            'data_dir' => self::TYPE_STRING,
            'language_dir' => self::TYPE_STRING,
            'site_cache_dir' => self::TYPE_STRING,
            'template_dir' => self::TYPE_STRING,
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

    public function getBasePath(): string
    {
        return $this->basePath ?: '';
    }

    public function getConfigDir(): string
    {
        return $this->configDir;
    }

    public function getGlobalParameters(): string
    {
        return $this->globalParameters;
    }

    public function getSitesDir(): string
    {
        return $this->sitesDir;
    }

    public function getConfigCacheDir(): string
    {
        return $this->configCacheDir;
    }

    public function getLogDir(): string
    {
        return $this->logDir;
    }

    public function getAssetDir(): string
    {
        return $this->assetDir;
    }

    public function getDataDir(): string
    {
        return $this->dataDir;
    }

    public function getLanguageDir(): string
    {
        return $this->languageDir;
    }

    public function getSiteCacheDir(): string
    {
        return $this->siteCacheDir;
    }

    public function getTemplateDir(): string
    {
        return $this->templateDir;
    }

    public function getError404PageName(): string
    {
        return $this->error404PageName;
    }

    public function getError500PageName(): string
    {
        return $this->error500PageName;
    }

    public function getImageCacheDir(): string
    {
        return $this->imageCacheDir;
    }

    public function getCacheNamespaceConfig(): string
    {
        return $this->cacheNamespaceConfig;
    }

    public function getCacheNamespaceAssets(): string
    {
        return $this->cacheNamespaceAssets;
    }

    private function convertIndexToProperty(string $index): string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $index))));
    }

    private function setArray(string $index, array $value): void
    {
        $this->{$this->convertIndexToProperty($index)} = $value;
    }

    private function setString(string $index, ?string $value): void
    {
        $this->{$this->convertIndexToProperty($index)} = $value;
    }
}
