<?php
    $finder = PhpCsFixer\Finder::create()
        ->in(['src', 'tests'])
        ->exclude('_support/_generated')
        ->notName('_bootstrap.php')
        ->notName('*Tester.php');

    return PhpCsFixer\Config::create()
        ->setRules([
           '@Symfony' => true,
           'array_syntax' => ['syntax' => 'short'],
           'concat_space' => ['spacing' => 'one'],
           'linebreak_after_opening_tag' => true,
           'ordered_imports' => true,
           'phpdoc_align' => false,
           'phpdoc_order' => true,
           'phpdoc_separation' => false,
           'phpdoc_summary' => false,
           'yoda_style' => false,
        ])
        ->setUsingCache(false)
        ->setFinder($finder);
