<?php

/*
 * This file is part of the Eventum (Issue Tracking System) package.
 *
 * @copyright (c) Eventum Team
 * @license GNU General Public License, version 2 or later (GPL-2+)
 *
 * For the full copyright and license information,
 * please see the COPYING and AUTHORS files
 * that were distributed with this source code.
 */

$header = <<<EOF
This file is part of the Eventum (Issue Tracking System) package.

@copyright (c) Eventum Team
@license GNU General Public License, version 2 or later (GPL-2+)

For the full copyright and license information,
please see the COPYING and AUTHORS files
that were distributed with this source code.
EOF;

$config = PhpCsFixer\Config::create();

# use git for defining input files
# https://github.com/FriendsOfPHP/PHP-CS-Fixer/issues/2214
$files = explode("\n", shell_exec('git ls-files'));
$finder = $config->getFinder()
    ->in(__DIR__)
    ->ignoreDotFiles(false)
    ->name('.php_cs')
    ->notPath('localization/LINGUAS.php')
    // this filter would accept only files that are present in Git
    ->filter(function (\SplFileInfo $file) use (&$files) {
        $key = array_search($file->getRelativePathname(), $files);
        if ($key) {
            error_log('ACCEPT: ' . $file->getRelativePathname());
        } else {
            error_log('REJECT: ' . $file->getRelativePathname());
        }

        return $key;
    });

$risky_rules = [
    'ereg_to_preg' => true,
    'no_alias_functions' => true,
    'no_php4_constructor' => true,
];

$symfony_rules = [
    'blank_line_after_opening_tag' => false,
    'blank_line_before_return' => true,
    'cast_spaces' => false,
    'concat_space' => ['spacing' => 'one'],
    'include' => true,
    'new_with_braces' => true,
    'no_blank_lines_after_class_opening' => true,
    'no_blank_lines_after_phpdoc' => false,
    'no_empty_statement' => true,
    'no_extra_consecutive_blank_lines' => true,
    'no_leading_import_slash' => true,
    'no_leading_namespace_whitespace' => true,
    'no_mixed_echo_print' => ['use' => 'echo'],
    'no_multiline_whitespace_around_double_arrow' => true,
    'no_singleline_whitespace_before_semicolons' => true,
    'no_trailing_comma_in_list_call' => true,
    'no_trailing_comma_in_singleline_array' => true,
    'no_unused_imports' => true,
    'no_whitespace_before_comma_in_array' => true,
    'no_whitespace_in_blank_line' => true,
    'object_operator_without_whitespace' => true,
    'phpdoc_align' => false,
    'phpdoc_annotation_without_dot' => true,
    'phpdoc_indent' => true,
    'phpdoc_inline_tag' => true,
    'phpdoc_no_access' => false, // RemoteApi relies on these tags
    'phpdoc_no_alias_tag' => ['type' => 'var', 'link' => 'see'],
    'phpdoc_no_empty_return' => true,
    'phpdoc_no_package' => true,
    'phpdoc_scalar' => true,
    'phpdoc_separation' => false,
    'phpdoc_single_line_var_spacing' => true,
    'phpdoc_summary' => false,
    'phpdoc_to_comment' => false,
    'phpdoc_trim' => true,
    'self_accessor' => true,
    'single_quote' => true,
    'standardize_not_equals' => true,
    'ternary_operator_spaces' => true,
    'trailing_comma_in_multiline_array' => true,
    'trim_array_spaces' => true,
    'whitespace_after_comma_in_array' => true,
];

#
# Try to use StyleCI "recommended" preset:
# https://styleci.readme.io/v1.0/docs/presets#recommended
$rules = $risky_rules + $symfony_rules + [
    '@PSR2' => true,
    'array_syntax' => ['syntax' => 'short'],
    'binary_operator_spaces' => ['align_double_arrow' => false],
    'braces' => ['allow_single_line_closure' => false],
    'function_declaration' => ['closure_function_spacing' => 'one'],
    'header_comment' => ['header' => $header],
    'linebreak_after_opening_tag' => false,
    'method_argument_space' => ['keep_multiple_spaces_after_comma' => false],
    'no_multiline_whitespace_before_semicolons' => true,
    'no_short_echo_tag' => true,
    'no_useless_else' => true,
    'no_useless_return' => true,
    'ordered_imports' => true,
    'phpdoc_order' => true,
    'semicolon_after_instruction' => true,
    'simplified_null_return' => false,
    'single_blank_line_before_namespace' => true,
    'strict_comparison' => false,
];

$cacheFile = sprintf('vendor/php_cs-%s.cache', PhpCsFixer\Console\Application::VERSION);
error_log("Cache: $cacheFile");

return $config
    ->setRiskyAllowed(true)
    ->setCacheFile($cacheFile)
    ->setRules($rules);

// vim:ft=php
