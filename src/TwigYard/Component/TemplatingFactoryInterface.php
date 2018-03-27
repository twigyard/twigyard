<?php

namespace TwigYard\Component;

interface TemplatingFactoryInterface
{
    /**
     * TemplatingFactoryInterface constructor.
     * @param string $templateDir
     * @param string $assetDir
     * @param TemplatingClosureFactory $tplClosureFactory
     * @param SiteTranslatorFactory $siteTranslatorFactory
     * @param string|null $siteCacheDir
     */
    public function __construct(
        string $templateDir,
        string $assetDir,
        TemplatingClosureFactory $tplClosureFactory,
        SiteTranslatorFactory $siteTranslatorFactory,
        ?string $siteCacheDir
    );

    /**
     * @param AppState $appState
     * @return TemplatingInterface
     */
    public function createTemplating(AppState $appState): TemplatingInterface;
}
