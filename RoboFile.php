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
            ->args('--standard=.php_cs_ruleset.xml')
            ->args('--encoding=utf-8')
            ->args(sprintf('--ignore=%s/**/_bootstrap.php', self::TESTS_DIR))
            ->args(sprintf('--ignore=%s/_support/*Tester.php', self::TESTS_DIR))
            ->args(implode(' ', [self::SRC_DIR, self::TESTS_DIR,]))
            ->run();

        $this
            ->taskExec('vendor/bin/phpcs')
            ->args('--standard=PSR2')
            ->args('--encoding=utf-8')
            ->args(sprintf('--ignore=%s/**/_bootstrap.php', self::TESTS_DIR))
            ->args(sprintf('--ignore=%s/_support/*Tester.php', self::TESTS_DIR))
            ->args(implode(' ', [self::SRC_DIR, self::TESTS_DIR,]))
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
