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
     * @var string|null
     */
    private $siteCacheDir;

    /**
     * @var string
     */
    private $templateDir;

    /**
     * @var string|null
     */
    private $parentDomain;

    /**
     * @var TemplatingClosureFactory
     */
    private $tplClosureFactory;

    /**
     * @var SiteTranslatorFactory
     */
    private $siteTranslatorFactory;

    /**
     * TwigTemplatingFactory constructor.
     */
    public function __construct(
        string $templateDir,
        string $assetDir,
        TemplatingClosureFactory $tplClosureFactory,
        SiteTranslatorFactory $siteTranslatorFactory,
        ?string $siteCacheDir,
        ?string $parentDomain
    ) {
        $this->templateDir = $templateDir;
        $this->assetDir = $assetDir;
        $this->tplClosureFactory = $tplClosureFactory;
        $this->siteTranslatorFactory = $siteTranslatorFactory;
        $this->siteCacheDir = $siteCacheDir;
        $this->parentDomain = $parentDomain;
    }

    public function createTemplating(AppState $appState): TemplatingInterface
    {
        return new TwigTemplating(
            $appState,
            $appState->getSiteDir() . '/' . $this->templateDir,
            $appState->getSiteDir() . '/' . $this->assetDir,
            $this->tplClosureFactory,
            $this->siteTranslatorFactory,
            $this->siteCacheDir
                ? ['cache' => $appState->getSiteDir() . '/' . $this->siteCacheDir . '/' . self::CACHE_DIR]
                : [],
            $this->parentDomain
        );
    }
}
