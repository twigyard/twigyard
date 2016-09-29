<?php

namespace TwigYard\Component;

use Symfony\Component\Translation\Loader\XliffFileLoader;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\Translation\Translator;

class TranslatorFactory
{
    const RESOURCES_TRANSLATIONS_PATHS = [
        '/vendor/symfony/validator/Resources/translations/validators.%s.xlf' => 'xliff',
        '/vendor/twigyard/twigyard/src/languages/messages.%s.yml' => 'yaml',
    ];
    
    const TRANSLATIONS_CACHE_DIR = '_translator';

    /**
     * @var AppState
     */
    private $appState;

    /**
     * @var string
     */
    private $appRoot;
    
    /**
     * @var string
     */
    private $cacheDir;
    
    /**
     * FormLoggerFactory constructor.
     * @param AppState $appState
     * @param string $appRoot
     * @param string $cacheDir
     */
    public function __construct(AppState $appState, $appRoot, $cacheDir)
    {
        $this->appState = $appState;
        $this->appRoot = $appRoot;
        $this->cacheDir = $cacheDir;
    }

    /**
     * @return Translator
     */
    public function getTranslator()
    {
        $locale = $this->appState->getLocale();
        $translator = new Translator(
            $locale,
            new MessageSelector(),
            $this->appRoot . '/' . $this->cacheDir . '/' . self::TRANSLATIONS_CACHE_DIR
        );

        $translator->addLoader('yaml', new YamlFileLoader());
        $translator->addLoader('xliff', new XliffFileLoader());

        foreach (self::RESOURCES_TRANSLATIONS_PATHS as $resourcePath => $format) {
            $translator->addResource(
                $format,
                $this->appRoot . sprintf($resourcePath, substr($locale, 0, 2)),
                $locale
            );
        }

        return $translator;
    }
}
