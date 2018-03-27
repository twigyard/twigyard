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
     */
    public function __construct(
        AppState $appState,
        string $templateDir,
        string $assetDir,
        TemplatingClosureFactory $tplClosureFactory,
        SiteTranslatorFactory $siteTranslatorFactory,
        array $options
    );

    /**
     * @param string $templateName
     * @return string
     */
    public function render(string $templateName): string;
}
