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
     * @param string|null $parentDomain
     */
    public function __construct(
        string $templateDir,
        string $assetDir,
        TemplatingClosureFactory $tplClosureFactory,
        SiteTranslatorFactory $siteTranslatorFactory,
        ?string $siteCacheDir,
        ?string $parentDomain
    );

    /**
     * @param AppState $appState
     * @return TemplatingInterface
     */
    public function createTemplating(AppState $appState): TemplatingInterface;
}
