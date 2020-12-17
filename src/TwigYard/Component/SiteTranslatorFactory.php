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
     * @var string|null
     */
    private $cacheDir;

    /**
     * @var string
     */
    private $languageResourcesDir;

    /**
     * FormLoggerFactory constructor.
     */
    public function __construct(string $languageResourcesDir, ?string $cacheDir)
    {
        $this->cacheDir = $cacheDir;
        $this->languageResourcesDir = $languageResourcesDir;
    }

    public function getTranslator(string $siteDir, string $locale): Translator
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
