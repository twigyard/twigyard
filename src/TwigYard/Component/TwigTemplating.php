<?php

namespace TwigYard\Component;

use Locale;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Twig_Environment;
use Twig_Extensions_Extension_Date;
use Twig_Extensions_Extension_I18n;
use Twig_Extensions_Extension_Intl;
use Twig_Extensions_Extension_Text;
use Twig_Loader_Filesystem;
use Twig_SimpleFunction;

class TwigTemplating implements TemplatingInterface
{
    /**
     * @var Twig_Environment
     */
    private $twigEnv;

    /**
     * @var \TwigYard\Component\AppState
     */
    private $appState;

    /**
     * @param \TwigYard\Component\AppState $appState
     * @param string $templateDir
     * @param string $languageDir
     * @param string $assetDir
     * @param \TwigYard\Component\TemplatingClosureFactory $tplClosureFactory
     * @param \TwigYard\Component\SiteTranslatorFactory $siteTranslatorFactory
     * @param array $options
     */
    public function __construct(
        AppState $appState,
        $templateDir,
        $languageDir,
        $assetDir,
        TemplatingClosureFactory $tplClosureFactory,
        SiteTranslatorFactory $siteTranslatorFactory,
        array $options
    ) {
        $this->appState = $appState;

        $this->twigEnv = new Twig_Environment(new Twig_Loader_Filesystem($templateDir), $options);
        $this->twigEnv->addExtension(new Twig_Extensions_Extension_Text());
        $this->twigEnv->addExtension(new Twig_Extensions_Extension_I18n());
        $this->twigEnv->addExtension(new Twig_Extensions_Extension_Intl());
        $this->twigEnv->addExtension(new Twig_Extensions_Extension_Date());
        $this->twigEnv->addExtension(
            new TranslationExtension(
                $siteTranslatorFactory->getTranslator(
                    $this->appState->getSiteDir(),
                    $this->appState->getLocale()
                )
            )
        );

        $this->twigEnv->addFunction(
            new Twig_SimpleFunction('asset', $tplClosureFactory->getAssetClosure(
                $assetDir,
                isset($options['cache']) ? $options['cache'] : null
            ))
        );
        $this->twigEnv->addFunction(
            new Twig_SimpleFunction('dump', $tplClosureFactory->getDumpClosure())
        );
        $this->twigEnv->addFunction(
            new Twig_SimpleFunction('image', $tplClosureFactory->getImageClosure($assetDir))
        );
        $this->twigEnv->addFunction(
            new Twig_SimpleFunction('path', $tplClosureFactory->getPathClosure(
                $this->appState->getRouteMap(),
                $appState->getLocale()
            ))
        );

        Locale::setDefault($appState->getLocale());
    }

    /**
     * @param string $templateName
     * @return string
     * @throws \Exception
     */
    public function render($templateName)
    {
        return $this->twigEnv->render($templateName, [
            'url' => $this->appState->getUrl(),
            'data' => $this->appState->getData(),
            'form' => $this->appState->getForm(),
            'locale' => $this->appState->getLocale(),
            'page' => $this->appState->getPage(),
            'url_params' => $this->appState->getUrlParams() ? $this->appState->getUrlParams() : [],
            'tracking' => $this->appState->getTrackingIds(),
        ]);
    }
}
