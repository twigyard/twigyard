<?php

namespace TwigYard\Component;

use Symfony\Component\Translation\Formatter\MessageFormatter;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;

class SiteTranslatorFactory
{
    const RESOURCES_TRANSLATIONS_PATH = '%s.yml';

    const TRANSLATIONS_CACHE_DIR = '_translator';

    /**
     * @var string
     */
    private $cacheDir;

    /**
     * @var string
     */
    private $languageResourcesDir;

    /**
     * FormLoggerFactory constructor.
     * @param string $cacheDir
     * @param string $languageResourcesDir
     */
    public function __construct($languageResourcesDir, $cacheDir)
    {
        $this->cacheDir = $cacheDir;
        $this->languageResourcesDir = $languageResourcesDir;
    }

    /**
     * @param $siteDir
     * @param $locale
     * @return Translator
     */
    public function getTranslator($siteDir, $locale)
    {
        $translator = new Translator(
            $locale,
            new MessageFormatter(),
            $this->cacheDir ? $siteDir . '/' . $this->cacheDir . '/' . self::TRANSLATIONS_CACHE_DIR : null
        );

        $translator->addLoader('yaml', new YamlFileLoader());
        $translator->addResource(
            'yaml',
            $siteDir . '/' . $this->languageResourcesDir . '/' . sprintf(self::RESOURCES_TRANSLATIONS_PATH, $locale),
            $locale
        );

        return $translator;
    }
}
