<?php

namespace TwigYard\Component;

use TwigYard\Exception\InvalidSiteConfigException;
use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\Yaml\Yaml;

class YamlConfigFileLoader extends FileLoader
{
    /**
     * @param mixed $resource
     * @param null $type
     * @return array
     */
    public function load($resource, $type = null)
    {
        $path = $this->locator->locate($resource);
        $content = $this->loadFile($path);

        if (null === $content) {
            return [];
        }

        if (!is_array($content)) {
            $content = [$content];
        }

        $this->parseImports($content, $path);

        return $content;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null)
    {
        return is_string($resource) && in_array(pathinfo($resource, PATHINFO_EXTENSION), array('yml', 'yaml'), true);
    }

    /**
     * @param $file
     * @return array
     * @throws \TwigYard\Exception\InvalidSiteConfigException
     */
    protected function loadFile($file)
    {
        if (!file_exists($file)) {
            throw new InvalidSiteConfigException(sprintf('The file "%s" is not valid.', $file));
        }

        return Yaml::parse(file_get_contents($file));
    }

    /**
     * @param array $content
     * @param string $file
     * @throws \TwigYard\Exception\InvalidSiteConfigException
     */
    private function parseImports(array &$content, $file)
    {
        if (!isset($content['imports'])) {
            return;
        }

        if (!is_array($content['imports'])) {
            throw new InvalidSiteConfigException(
                sprintf('The "imports" key should contain an array in %s. Check your YAML syntax.', $file)
            );
        }

        $defaultDirectory = dirname($file);
        foreach ($content['imports'] as $import) {
            if (!is_array($import)) {
                throw new InvalidSiteConfigException(
                    sprintf('The values in the "imports" key should be arrays in %s. Check your YAML syntax.', $file)
                );
            }

            $this->setCurrentDir($defaultDirectory);
            $content = array_replace_recursive(
                $this->import(
                    $import['resource'],
                    null,
                    isset($import['ignore_errors']) ? (bool) $import['ignore_errors'] : false,
                    $file
                ),
                $content
            );
        }
        unset($content['imports']);
    }
}
