<?php

namespace TwigYard\Unit\Component;

use Nette\Caching\Cache;
use Prophecy\Argument\Token\AnyValueToken;
use Prophecy\Prophet;
use Psr\Log\LoggerInterface;
use TwigYard\Component\ConfigCache;
use TwigYard\Component\LoggerFactory;
use VirtualFileSystem\FileSystem;

class ConfigCacheCest
{
    /**
     * @param \UnitTester $I
     */
    public function ignoreMissingOrInvalidSiteYml(\UnitTester $I)
    {
        $prophet = new Prophet();
        $fs = new FileSystem();
        $fs->createDirectory('/sites/www.example.com', true);
        file_put_contents($fs->path('/sites/www.example.com/site.yml'), 'Invalid yml content');
        $fs->createDirectory('/sites/www.example2.com', true);
        $cache = $prophet->prophesize(Cache::class);
        $cache->load(new AnyValueToken(), new AnyValueToken())->will(function ($args) {
            return $args[1]('/sites', 'site.yml');
        });
        $configCache = new ConfigCache($cache->reveal(), $this->getLoggerFactory($prophet)->reveal());
        $configs = $configCache->getConfig($fs->path('/sites'), 'site.yml');
        $I->assertEquals([], $configs);
    }

    /**
     * @param \UnitTester $I
     */
    public function ignoreYamlOnDuplicateCanonicalUrl(\UnitTester $I)
    {
        $prophet = new Prophet();
        $fs = new FileSystem();
        $this->createExample($fs);

        $fs->createDirectory('/sites/www.example2.com', true);
        $config = <<<EOT
url:
    canonical: www.example1.com
    extra: [ example2.com ]
EOT;
        file_put_contents($fs->path('/sites/www.example2.com/site.yml'), $config);

        $cache = $prophet->prophesize(Cache::class);
        $cache->load(new AnyValueToken(), new AnyValueToken())->will(function ($args) use ($fs) {
            return $args[1]($fs->path('/sites'), 'site.yml');
        });
        $configCache = new ConfigCache($cache->reveal(), $this->getLoggerFactory($prophet)->reveal());
        $configs = $configCache->getConfig($fs->path('/sites'), 'site.yml');
        $I->assertEquals([
            'www.example1.com' => ['url' => ['canonical' => 'www.example1.com', 'extra' => ['example1.com']]],
            'example1.com' => ['url' => ['canonical' => 'www.example1.com', 'extra' => ['example1.com']]],
        ], $configs);
    }

    /**
     * @param \UnitTester $I
     */
    public function ignoreYamlOnDuplicateExtraUrl(\UnitTester $I)
    {
        $prophet = new Prophet();
        $fs = new FileSystem();
        $this->createExample($fs);

        $fs->createDirectory('/sites/www.example2.com', true);
        $config = <<<EOT
url:
    canonical: www.example2.com
    extra: [ example1.com ]
EOT;
        file_put_contents($fs->path('/sites/www.example2.com/site.yml'), $config);

        $cache = $prophet->prophesize(Cache::class);
        $cache->load(new AnyValueToken(), new AnyValueToken())->will(function ($args) use ($fs) {
            return $args[1]($fs->path('/sites'), 'site.yml');
        });
        $configCache = new ConfigCache($cache->reveal(), $this->getLoggerFactory($prophet)->reveal());
        $configs = $configCache->getConfig($fs->path('/sites'), 'site.yml');
        $I->assertEquals([
            'www.example1.com' => ['url' => ['canonical' => 'www.example1.com', 'extra' => ['example1.com']]],
            'example1.com' => ['url' => ['canonical' => 'www.example1.com', 'extra' => ['example1.com']]],
        ], $configs);
    }

    /**
     * @param \UnitTester $I
     */
    public function emptyExtraUrl(\UnitTester $I)
    {
        $prophet = new Prophet();
        $fs = new FileSystem();

        $fs->createDirectory('/sites/www.example.com', true);
        $config = <<<EOT
url:
    canonical: www.example.com
EOT;
        file_put_contents($fs->path('/sites/www.example.com/site.yml'), $config);

        $cache = $prophet->prophesize(Cache::class);
        $cache->load(new AnyValueToken(), new AnyValueToken())->will(function ($args) use ($fs) {
            return $args[1]($fs->path('/sites'), 'site.yml');
        });
        $configCache = new ConfigCache($cache->reveal(), $this->getLoggerFactory($prophet)->reveal());
        $configs = $configCache->getConfig($fs->path('/sites'), 'site.yml');
        $I->assertEquals([
            'www.example.com' => ['url' => ['canonical' => 'www.example.com']],
        ], $configs);
    }

    /**
     * @param \UnitTester $I
     */
    public function prepareRedirectsMap(\UnitTester $I)
    {
        $prophet = new Prophet();
        $fs = new FileSystem();
        $fs->createDirectory('/sites/www.example1.com', true);

        $config = <<<EOT
url:
    canonical: www.example1.com
    extra: [ example1.com ]

redirect:
    url/old: url/new
    url/old2: url/new2
EOT;
        file_put_contents($fs->path('/sites/www.example1.com/site.yml'), $config);

        $cache = $prophet->prophesize(Cache::class);
        $cache->load(new AnyValueToken(), new AnyValueToken())->will(function ($args) use ($fs) {
            return $args[1]($fs->path('/sites'), 'site.yml');
        });
        $configCache = new ConfigCache($cache->reveal(), $this->getLoggerFactory($prophet)->reveal());
        $configs = $configCache->getConfig($fs->path('/sites'), 'site.yml');
        $I->assertEquals([
            'www.example1.com' => [
                'url' => ['canonical' => 'www.example1.com', 'extra' => ['example1.com']],
                'redirect' => ['url/old' => 'url/new', 'url/old2' => 'url/new2'],
            ],
            'example1.com' => [
                'url' => ['canonical' => 'www.example1.com', 'extra' => ['example1.com']],
                'redirect' => ['url/old' => 'url/new', 'url/old2' => 'url/new2'],
            ],
        ], $configs);
    }

    /**
     * @param \UnitTester $I
     */
    public function importSiteConfig(\UnitTester $I)
    {
        $prophet = new Prophet();
        $fs = new FileSystem();

        $fs->createDirectory('/sites/www.example1.com', true);
        $config = <<<EOT
url:
    canonical: www.example1.com
    extra: []
mw:
    config_key: parent_value
    config_key2: parent_value2
parent_mw:
    config_key2: only_parent_value
EOT;
        file_put_contents($fs->path('/sites/www.example1.com/main.yml'), $config);
        $config = <<<EOT
imports:
    - { resource: 'main.yml' }
mw:
    config_key: specific_value
    config_key3: specific_value3
specific_mw:
    config_key3: only_specific_value
EOT;
        file_put_contents($fs->path('/sites/www.example1.com/specific.yml'), $config);

        $cache = $prophet->prophesize(Cache::class);
        $cache->load(new AnyValueToken(), new AnyValueToken())->will(function ($args) use ($fs) {
            return $args[1]($fs->path('/sites'), 'specific.yml');
        });
        $configCache = new ConfigCache($cache->reveal(), $this->getLoggerFactory($prophet)->reveal());
        $configs = $configCache->getConfig($fs->path('/sites'), 'specific.yml');
        $I->assertEquals([
            'www.example1.com' => [
                'url' => ['canonical' => 'www.example1.com', 'extra' => []],
                'mw' => [
                    'config_key' => 'specific_value',
                    'config_key2' => 'parent_value2',
                    'config_key3' => 'specific_value3',
                ],
                'parent_mw' => ['config_key2' => 'only_parent_value'],
                'specific_mw' => ['config_key3' => 'only_specific_value'], ],
        ], $configs);
    }

    /**
     * @param \VirtualFileSystem\FileSystem $fs
     */
    private function createExample(FileSystem $fs)
    {
        $fs->createDirectory('/sites/www.example1.com', true);
        $config = <<<EOT
url:
    canonical: www.example1.com
    extra: [ example1.com ]
EOT;
        file_put_contents($fs->path('/sites/www.example1.com/site.yml'), $config);
    }

    /**
     * @param \Prophecy\Prophet $prophet
     * @return \Prophecy\Prophecy\ObjectProphecy
     */
    private function getLoggerFactory(Prophet $prophet)
    {
        $logger = $prophet->prophesize(LoggerInterface::class);
        $loggerFactory = $prophet->prophesize(LoggerFactory::class);
        $loggerFactory->getLogger(new AnyValueToken())->willReturn($logger->reveal());

        return $loggerFactory;
    }
}
