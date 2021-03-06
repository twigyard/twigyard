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
     * @var AppState
     */
    private $appState;

    /**
     * @var string|null
     */
    private $parentDomain;

    /**
     * TwigTemplating constructor.
     */
    public function __construct(
        AppState $appState,
        string $templateDir,
        string $assetDir,
        TemplatingClosureFactory $tplClosureFactory,
        SiteTranslatorFactory $siteTranslatorFactory,
        array $options,
        ?string $parentDomain
    ) {
        $this->appState = $appState;
        $this->parentDomain = $parentDomain;

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
     * @throws \Exception
     */
    public function render(string $templateName): string
    {
        return $this->twigEnv->render($templateName, [
            'url' => $this->parentDomain
                ? sprintf('%s.%s', $this->appState->getUrl(), $this->parentDomain)
                : $this->appState->getUrl(),
            'data' => $this->appState->getData(),
            'form' => $this->appState->getForm(),
            'locale' => $this->appState->getLocale(),
            'page' => $this->appState->getPage(),
            'scheme' => $this->appState->getScheme(),
            'url_params' => $this->appState->getUrlParams() ? $this->appState->getUrlParams() : [],
            'tracking' => $this->appState->getTrackingIds(),
        ]);
    }
}
