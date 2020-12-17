<?php

namespace TwigYard\Component;

interface TemplatingInterface
{
    /**
     * TemplatingInterface constructor.
     */
    public function __construct(
        AppState $appState,
        string $templateDir,
        string $assetDir,
        TemplatingClosureFactory $tplClosureFactory,
        SiteTranslatorFactory $siteTranslatorFactory,
        array $options,
        ?string $parentDomain
    );

    public function render(string $templateName): string;
}
