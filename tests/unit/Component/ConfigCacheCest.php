<?php

namespace TwigYard\Unit\Component;

use Nette\Caching\Cache;
use Prophecy\Argument\Token\AnyValueToken;
use Prophecy\Prophet;
use Psr\Log\LoggerInterface;
use TwigYard\Component\ConfigCache;
use TwigYard\Component\LoggerFactory;
use VirtualFileSystem\Exception\FileExistsException;
use VirtualFileSystem\FileSystem;

class ConfigCacheCest
{
    /**
     * @param \UnitTester $I
     */
    public function runTests(\UnitTester $I)
    {
        foreach ($this->getTestData() as $configVersion => $versionData) {
            foreach ($versionData as $testData) {
                $prophet = new Prophet();
                $fs = new FileSystem();
                foreach ($testData['configYamls'] as $configDir => $configYaml) {
                    $this->createExample($fs, $configDir, $configYaml);
                }
                $cache = $prophet->prophesize(Cache::class);
                $cache->load(new AnyValueToken(), new AnyValueToken())->will(function ($args) use ($fs) {
                    return $args[1]($fs->path('/sites'), 'site.yml');
                });
                $configCache = new ConfigCache(
                    $cache->reveal(),
                    $this->getLoggerFactory($prophet)->reveal(),
                    $fs->path('/sites'),
                    'site.yml'
                );

                $I->assertEquals(
                    $testData['configArray'],
                    $configCache->getConfig(),
                    $configVersion . ' ' . $testData['description']
                );
            }
        }
    }

    /**
     * @param \VirtualFileSystem\FileSystem $fs
     * @param string|null $dirName
     * @param string|null $config
     */
    private function createExample(FileSystem $fs, string $dirName, ?string $config): void
    {
        try {
            $fs->createDirectory($dirName, true);
            if ($config !== null) {
                file_put_contents($fs->path($dirName . '/site.yml'), $config);
            }
        } catch (FileExistsException $e) {
        }
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

    /**
     * @return array
     */
    public function getTestData(): array
    {
        return [
            'common' => [
                [
                    'description' => 'ignores invalid site.yml',
                    'configYamls' => [
                        '/sites/www.example.com' => 'Some invalid yaml',
                    ],
                    'configArray' => [],
                ],
                [
                    'description' => 'ignores site.yml with invalid config version',
                    'configYamls' => [
                        '/sites/www.example.com' => "version: 999\nurl:\n  canonical: www.example.com",
                    ],
                    'configArray' => [],
                ],
                [
                    'description' => 'ignores site.yml with missing parameter',
                    'configYamls' => [
                        '/sites/www.example.com' => "url: '%test_param%'\nparameters: []",
                    ],
                    'configArray' => [],
                ],
                [
                    'description' => 'ignores missing site.yml',
                    'configYamls' => [
                        '/sites/www.example.com' => null,
                    ],
                    'configArray' => [],
                ],
            ],
            'version1' => [
                [
                    'description' => 'empty extra url',
                    'configYamls' => [
                        '/sites/www.example.com' => "url:\n  canonical: www.example.com",
                    ],
                    'configArray' => ['www.example.com' => [
                        'version' => 1,
                        'url' => ['canonical' => 'www.example.com'],
                    ]],
                ],
                [
                    'description' => 'ignore yaml on double canonical url',
                    'configYamls' => [
                        '/sites/www.example1.com' => "url:\n  canonical: www.example1.com",
                        '/sites/www.example2.com' => "url:\n  canonical: www.example1.com",
                    ],
                    'configArray' => ['www.example1.com' => [
                        'version' => 1,
                        'url' => ['canonical' => 'www.example1.com'],
                    ]],
                ],
                [
                    'description' => 'ignore yaml on double extra url',
                    'configYamls' => [
                        '/sites/www.example1.com' => "url:\n  canonical: www.example1.com\n  extra: [example1.com]",
                        '/sites/www.example2.com' => "url:\n  canonical: www.example2.com\n  extra: [example1.com]",
                    ],
                    'configArray' => [
                        'www.example1.com' => [
                            'version' => 1,
                            'url' => ['canonical' => 'www.example1.com', 'extra' => ['example1.com']],
                        ],
                        'example1.com' => [
                            'version' => 1,
                            'url' => ['canonical' => 'www.example1.com', 'extra' => ['example1.com']],
                        ],
                    ],
                ],
                [
                    'description' => 'loads config',
                    'configYamls' => [
                        '/sites/www.example1.com' => "parameters:\n  test_param: example1.com\nurl:\n  canonical: www.example1.com\n  extra: ['%test_param%']",
                        '/sites/www.example2.com' => "url:\n  canonical: www.example2.com\n  extra: [example2.com]",
                    ],
                    'configArray' => [
                        'www.example1.com' => [
                            'version' => 1,
                            'url' => ['canonical' => 'www.example1.com', 'extra' => ['example1.com']],
                            'parameters' => ['test_param' => 'example1.com'],
                        ],
                        'example1.com' => [
                            'version' => 1,
                            'url' => ['canonical' => 'www.example1.com', 'extra' => ['example1.com']],
                            'parameters' => ['test_param' => 'example1.com'],
                        ],
                        'www.example2.com' => [
                            'version' => 1,
                            'url' => ['canonical' => 'www.example2.com', 'extra' => ['example2.com']],
                        ],
                        'example2.com' => [
                            'version' => 1,
                            'url' => ['canonical' => 'www.example2.com', 'extra' => ['example2.com']],
                        ],
                    ],
                ],
            ],
            'version2' => [
                [
                    'description' => 'empty extra url',
                    'configYamls' => [
                        '/sites/www.example.com' => "version: 2\nmiddlewares:\n  url:\n    canonical: www.example.com",
                    ],
                    'configArray' => ['www.example.com' => [
                        'version' => 2,
                        'middlewares' => ['url' => ['canonical' => 'www.example.com']],
                    ]],
                ],
                [
                    'description' => 'ignore yaml on double canonical url',
                    'configYamls' => [
                        '/sites/www.example1.com' => "version: 2\nmiddlewares:\n  url:\n    canonical: www.example1.com",
                        '/sites/www.example2.com' => "version: 2\nmiddlewares:\n  url:\n    canonical: www.example1.com",
                    ],
                    'configArray' => ['www.example1.com' => [
                        'version' => 2,
                        'middlewares' => ['url' => ['canonical' => 'www.example1.com']],
                    ]],
                ],
                [
                    'description' => 'ignore yaml on double extra url',
                    'configYamls' => [
                        '/sites/www.example1.com' => "version: 2\nmiddlewares:\n  url:\n    canonical: www.example1.com\n    extra: [example1.com]",
                    ],
                    'configArray' => [
                        'www.example1.com' => [
                            'version' => 2,
                            'middlewares' => [
                                'url' => ['canonical' => 'www.example1.com', 'extra' => ['example1.com']],
                            ],
                        ],
                        'example1.com' => [
                            'version' => 2,
                            'middlewares' => [
                                'url' => ['canonical' => 'www.example1.com', 'extra' => ['example1.com']],
                            ],
                        ],
                    ],
                ],
                [
                    'description' => 'loads config',
                    'configYamls' => [
                        '/sites/www.example1.com' => "parameters:\n  test_param: test_param_val\nversion: 2\nmiddlewares:\n  url:\n    canonical: www.example1.com\n    extra: ['%test_param%']",
                        '/sites/www.example2.com' => "version: 2\nmiddlewares:\n  url:\n    canonical: www.example2.com\n    extra: [example2.com]",
                    ],
                    'configArray' => [
                        'www.example1.com' => [
                            'version' => 2,
                            'middlewares' => [
                                'url' => ['canonical' => 'www.example1.com', 'extra' => ['test_param_val']],
                            ],
                            'parameters' => ['test_param' => 'test_param_val'],
                        ],
                        'test_param_val' => [
                            'version' => 2,
                            'middlewares' => [
                                'url' => ['canonical' => 'www.example1.com', 'extra' => ['test_param_val']],
                            ],
                            'parameters' => ['test_param' => 'test_param_val'],
                        ],
                        'www.example2.com' => [
                            'version' => 2,
                            'middlewares' => [
                                'url' => ['canonical' => 'www.example2.com', 'extra' => ['example2.com']],
                            ],
                        ],
                        'example2.com' => [
                            'version' => 2,
                            'middlewares' => [
                                'url' => ['canonical' => 'www.example2.com', 'extra' => ['example2.com']],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
