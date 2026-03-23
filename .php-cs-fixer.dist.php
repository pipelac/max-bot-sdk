<?php

declare(strict_types=1);

/**
 * PHP CS Fixer конфигурация для MAX Bot API SDK v2.
 *
 * PHP 8.1+ / PER-CS 2.0
 */
$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests');

return (new PhpCsFixer\Config())
    ->setRules([
        '@PER-CS2.0'                       => true,
        'declare_strict_types'             => true,
        'array_syntax'                     => ['syntax' => 'short'],
        'no_unused_imports'                => true,
        'ordered_imports'                  => ['sort_algorithm' => 'alpha'],
        'single_blank_line_at_eof'         => true,
        'no_trailing_whitespace'           => true,
        'blank_line_after_opening_tag'     => true,
        'no_empty_statement'               => true,
        'no_extra_blank_lines'             => true,
        'trailing_comma_in_multiline'      => ['elements' => ['arguments', 'arrays', 'match', 'parameters']],
        'native_function_invocation'       => ['include' => ['@compiler_optimized'], 'scope' => 'namespaced'],
        'global_namespace_import'          => ['import_classes' => true, 'import_functions' => false, 'import_constants' => false],
        'nullable_type_declaration'        => true,
        'void_return'                      => true,
        'fully_qualified_strict_types'     => true,
    ])
    ->setFinder($finder)
    ->setUsingCache(true);
