<?php

namespace TwigYard\Component;

interface TemplatingInterface
{
    /**
     * @param \TwigYard\Component\AppState $appState
     * @param string $templateDir
     * @param string $languageDir
     * @param string $assetDir
     * @param \TwigYard\Component\TemplatingClosureFactory $tplClosureFactory
     * @param array $options
     */
    public function __construct(
        AppState $appState,
        $templateDir,
        $languageDir,
        $assetDir,
        TemplatingClosureFactory $tplClosureFactory,
        array $options
    );

    /**
     * @param string $templateName
     * @return string
     */
    public function render($templateName);
}
