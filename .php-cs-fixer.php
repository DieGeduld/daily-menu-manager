<?php

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__ . '/includes',
        __DIR__ . '/tests',
    ])
    ->name('*.php')
    ->notName('*.blade.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

$config = new PhpCsFixer\Config();

return $config->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'no_unused_imports' => true,
        'not_operator_with_space' => false,  // Kein Leerzeichen um den Negationsoperator
        'not_operator_with_successor_space' => false,  // Kein Leerzeichen nach dem Negationsoperator
        'trailing_comma_in_multiline' => true,
        'phpdoc_scalar' => true,
        'unary_operator_spaces' => true,
        'binary_operator_spaces' => true,
        'blank_line_before_statement' => [
            'statements' => ['break', 'continue', 'declare', 'return', 'throw', 'try'],
        ],
        'phpdoc_single_line_var_spacing' => true,
        'phpdoc_var_without_name' => true,
        'method_argument_space' => [
            'on_multiline' => 'ensure_fully_multiline',
            'keep_multiple_spaces_after_comma' => true,
        ],
        'single_trait_insert_per_statement' => true,
        'no_extra_blank_lines' => [
            'tokens' => [
                'extra',
                'throw',
                'use',
                'use_trait',
                'return',
            ],
        ],
        'indentation_type' => true,
        'blank_line_after_namespace' => true,  // Eine Leerzeile nach Namespace-Deklaration
        'no_extra_blank_lines' => [
            'tokens' => [
                'curly_brace_block',  // Kontrolliert Leerzeilen in Blöcken mit geschweiften Klammern
                'extra',
                'parenthesis_brace_block',
                'square_brace_block',
                'throw',
                'use',
            ],
        ],
    ])
    ->setFinder($finder)
    ->setUsingCache(true);
