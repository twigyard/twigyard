<?php

namespace TwigYard\Component;

interface TemplatingFactoryInterface
{
    /**
     * TemplatingFactoryInterface constructor.
     */
    public function __construct(
        string $templateDir,
        string $assetDir,
        TemplatingClosureFactory $tplClosureFactory,
        SiteTranslatorFactory $siteTranslatorFactory,
        ?string $siteCacheDir,
        ?string $parentDomain
    );

    public function createTemplating(AppState $appState): TemplatingInterface;
}
