<?php

namespace TwigYard\Component;

use Symfony\Component\Translation\Loader\XliffFileLoader;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\Translation\Translator;

class SiteTranslatorFactory
{
    const RESOURCES_TRANSLATIONS_PATH = '%s.yml';

    const TRANSLATIONS_CACHE_DIR = '_translator';

    /**
     * @var AppState
     */
    private $appState;

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
     * @param AppState $appState
     * @param string $cacheDir
     * @param string $languageResourcesDir
     */
    public function __construct(AppState $appState, $languageResourcesDir, $cacheDir)
    {
        $this->appState = $appState;
        $this->cacheDir = $cacheDir;
        $this->languageResourcesDir = $languageResourcesDir;
    }

    /**
     * @param string $siteDir
     * @return Translator
     */
    public function getTranslator($siteDir)
    {
        $locale = $this->appState->getLocale();
        $translator = new Translator(
            $locale,
            new MessageSelector(),
            $siteDir . '/' . $this->cacheDir . '/' . self::TRANSLATIONS_CACHE_DIR
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
