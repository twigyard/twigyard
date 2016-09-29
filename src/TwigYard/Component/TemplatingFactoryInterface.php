<?php

namespace TwigYard\Component;

interface TemplatingFactoryInterface
{
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
    );

    /**
     * @param \TwigYard\Component\AppState $appState
     * @return \TwigYard\Component\TemplatingInterface
     */
    public function createTemplating(AppState $appState);
}
