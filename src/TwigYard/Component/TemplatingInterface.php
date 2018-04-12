<?php

namespace TwigYard\Component;

interface TemplatingInterface
{
    /**
     * TemplatingInterface constructor.
     * @param AppState $appState
     * @param string $templateDir
     * @param string $assetDir
     * @param TemplatingClosureFactory $tplClosureFactory
     * @param SiteTranslatorFactory $siteTranslatorFactory
     * @param array $options
     * @param string|null $parentDomain
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

    /**
     * @param string $templateName
     * @return string
     */
    public function render(string $templateName): string;
}
