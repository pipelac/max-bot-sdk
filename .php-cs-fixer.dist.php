<?php

/**
 * PHP CS Fixer конфигурация для MAX Bot API SDK.
 *
 * Совместима с PHP 5.6+.
 */
$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests');

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR2'                  => true,
        'array_syntax'           => ['syntax' => 'short'],
        'no_unused_imports'      => true,
        'ordered_imports'        => true,
        'single_blank_line_at_eof' => true,
        'no_trailing_whitespace' => true,
        'blank_line_after_opening_tag' => true,
        'no_empty_statement'     => true,
        'no_extra_blank_lines'   => true,
    ])
    ->setFinder($finder)
    ->setUsingCache(true);
