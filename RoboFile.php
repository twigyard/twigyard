<?php

use Robo\Tasks;

class RoboFile extends Tasks
{
    const SRC_DIR = __DIR__ . '/src';
    const TESTS_DIR = __DIR__ . '/tests';

    public function lintPhp()
    {
        $this
            ->taskExec(vsprintf('find %s -name "*.php" -print0 | xargs -0 -n1 -P8 php -l', [
                implode(' ', [
                    self::SRC_DIR,
                    self::TESTS_DIR,
                ]),
            ]))
            ->run();
    }

    public function codecept($suite = null)
    {
        $task = $this->taskCodecept('vendor/bin/codecept');
        if ($suite) {
            $task = $task->suite($suite);
        }
        $task->run();
    }

    public function phpcs()
    {
        $this
            ->taskExec('vendor/bin/phpcs')
            ->arg('--standard=.php_cs_ruleset.xml')
            ->arg('--encoding=utf-8')
            ->arg(sprintf('--ignore=%s/**/_bootstrap.php', self::TESTS_DIR))
            ->arg(sprintf('--ignore=%s/_support/*Tester.php', self::TESTS_DIR))
            ->arg(self::SRC_DIR)
            ->arg(self::TESTS_DIR)
            ->run();

        $this
            ->taskExec('vendor/bin/phpcs')
            ->arg('--standard=PSR2')
            ->arg('--encoding=utf-8')
            ->arg(sprintf('--ignore=%s/**/_bootstrap.php', self::TESTS_DIR))
            ->arg(sprintf('--ignore=%s/_support/*Tester.php', self::TESTS_DIR))
            ->arg(self::SRC_DIR)
            ->arg(self::TESTS_DIR)
            ->run();
    }

    public function test()
    {
        $this->stopOnFail(true);
        $this->lintPhp();
        $this->phpcs();
        $this->codecept();
    }
}
