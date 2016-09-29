<?php

namespace TwigYard\Component;

class TwigTemplatingFactory implements TemplatingFactoryInterface
{
    const CACHE_DIR = '_twig';

    /**
     * @var string
     */
    private $assetDir;

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
    private $languageDir;

    /**
     * @var \TwigYard\Component\TemplatingClosureFactory
     */
    private $tplClosureFactory;

    /**
     * @param string $templateDir
     * @param string $languageDir
     * @param string $assetDir
     * @param \TwigYard\Component\TemplatingClosureFactory $tplClosureFactory
     * @param string $siteCacheDir
     */
    public function __construct(
        $templateDir,
        $languageDir,
        $assetDir,
        TemplatingClosureFactory $tplClosureFactory,
        $siteCacheDir
    ) {
        $this->templateDir = $templateDir;
        $this->languageDir = $languageDir;
        $this->assetDir = $assetDir;
        $this->tplClosureFactory = $tplClosureFactory;
        $this->siteCacheDir = $siteCacheDir;
    }

    /**
     * @param \TwigYard\Component\AppState $appState
     * @return \TwigYard\Component\TwigTemplating
     */
    public function createTemplating(AppState $appState)
    {
        return new TwigTemplating(
            $appState,
            $appState->getSiteDir() . '/' . $this->templateDir,
            $appState->getSiteDir() . '/' . $this->languageDir,
            $appState->getSiteDir() . '/' . $this->assetDir,
            $this->tplClosureFactory,
            $this->siteCacheDir
                ? ['cache' => $appState->getSiteDir() . '/' . $this->siteCacheDir . '/' . self::CACHE_DIR]
                : []
        );
    }
}
