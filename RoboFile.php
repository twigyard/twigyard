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
            ->arg(sprintf('--ignore=%s/**/_bootstrap.php,%s/_support/*Tester.php', self::TESTS_DIR, self::TESTS_DIR))
            ->arg(self::SRC_DIR)
            ->arg(self::TESTS_DIR)
            ->run();

        $this
            ->taskExec('vendor/bin/php-cs-fixer fix')
            ->args('--dry-run')
            ->args('--diff')
            ->args('--config', '.php_cs')
            ->run();
    }

    public function phpcsFix()
    {
        $this
            ->taskExec('vendor/bin/php-cs-fixer fix')
            ->args('--diff')
            ->args('--config', '.php_cs')
            ->run();
    }

    public function phpstan()
    {
        $this
            ->taskExec('vendor/bin/phpstan')
            ->args('analyze')
            ->args(self::SRC_DIR)
            ->args('-c', 'phpstan.neon')
            ->args('--level', 7)
            ->run();
    }

    public function test()
    {
        $this->stopOnFail(true);
        $this->lintPhp();
        $this->phpcs();
        $this->phpstan();
        $this->codecept();
    }
}
