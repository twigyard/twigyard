<?php

namespace TwigYard\Component;

use Symfony\Component\Translation\Formatter\MessageFormatter;
use Symfony\Component\Translation\Loader\XliffFileLoader;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;

class TranslatorFactory
{
    const RESOURCES_TRANSLATIONS_PATHS = [
        '/vendor/symfony/validator/Resources/translations/validators.%s.xlf' => 'xliff',
        '/vendor/twigyard/twigyard/src/TwigYard/languages/messages.%s.yml' => 'yaml',
    ];

    const TRANSLATIONS_CACHE_DIR = '_translator';

    /**
     * @var string
     */
    private $appRoot;

    /**
     * @var string|null
     */
    private $cacheDir;

    /**
     * TranslatorFactory constructor.
     * @param string $appRoot
     * @param string|null $cacheDir
     */
    public function __construct(string $appRoot, ?string $cacheDir)
    {
        $this->appRoot = $appRoot;
        $this->cacheDir = $cacheDir;
    }

    /**
     * @param string $locale
     * @return Translator
     */
    public function getTranslator(string $locale): Translator
    {
        $translator = new Translator(
            $locale,
            new MessageFormatter(),
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
