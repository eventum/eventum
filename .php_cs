<?php

$header = <<<EOF
This file is part of the Eventum (Issue Tracking System) package.

@copyright (c) Eventum Team
@license GNU General Public License, version 2 or later (GPL-2+)

For the full copyright and license information,
please see the COPYING and AUTHORS files
that were distributed with this source code.
EOF;

Symfony\CS\Fixer\Contrib\HeaderCommentFixer::setHeader($header);

# use git for defining input files
# https://github.com/FriendsOfPHP/PHP-CS-Fixer/issues/2214

$files = explode("\n", shell_exec('git ls-files'));
$finder = Symfony\CS\Finder\DefaultFinder::create()
	->in(__DIR__)
	->notPath('localization/LINGUAS.php')
	// this filter would accept only files that are present in Git
	->filter(function(\SplFileInfo $file) use (&$files) {
		$key = array_search($file->getRelativePathname(), $files);
		if ($key) {
			error_log("ACCEPT: ".$file->getRelativePathname());
		} else {
			error_log("REJECT: ".$file->getRelativePathname());
		}
		return $key;
	})
;

return Symfony\CS\Config\Config::create()
	->setUsingCache(true)
	->level(Symfony\CS\FixerInterface::NONE_LEVEL)
	# before sort: sed -e "/'-/ {s/-//;s/',/-',/}" .php_cs
	#  after sort: sed -e "/-'/ {s/-//;s/'/'-/}" .php_cs
	#
	# Try to use StyleCI "recommended" preset:
	# https://styleci.readme.io/v1.0/docs/presets#recommended
	->fixers(array(
		'-align_double_arrow',
		'-align_equals',
		'-blankline_after_open_tag',
		'braces',
		'-concat_with_spaces',
		'-concat_without_spaces',
		'double_arrow_multiline_whitespaces',
		'duplicate_semicolon',
		'elseif',
		'-empty_return',
		'encoding',
		'eof_ending',
		'ereg_to_preg',
		'extra_empty_lines',
		'function_call_space',
		'function_declaration',
		'header_comment',
		'-header_comment',
		'include',
		'indentation',
		'join_function',
		'line_after_namespace',
		'linefeed',
		'list_commas',
		'lowercase_constants',
		'lowercase_keywords',
		'method_argument_space',
		'-multiline_array_trailing_comma',
		'multiline_spaces_before_semicolon',
		'multiple_use',
		'namespace_no_leading_whitespace',
		'new_with_braces',
		'-newline_after_open_tag',
		'no_blank_lines_after_class_opening',
		'-no_blank_lines_before_namespace',
		'-no_empty_lines_after_phpdocs',
		'object_operator',
		'-operators_spaces',
		'ordered_use',
		'parenthesis',
		'php4_constructor',
		'php_closing_tag',
		'-phpdoc_indent',
		'-phpdoc_inline_tag',
		'-phpdoc_no_access', // RemoteApi.php uses @access tags internally
		'-phpdoc_no_empty_return',
		'-phpdoc_no_package',
		'-phpdoc_order',
		'-phpdoc_params',
		'-phpdoc_scalar',
		'-phpdoc_separation',
		'-phpdoc_short_description',
		'-phpdoc_to_comment',
		'-phpdoc_trim',
		'-phpdoc_type_to_var',
		'-phpdoc_var_to_type',
		'-phpdoc_var_without_name',
		'print_to_echo',
		'remove_leading_slash_use',
		'remove_lines_between_uses',
		'return',
		'self_accessor',
		'short_array_syntax',
		'short_tag',
		'single_array_no_trailing_comma',
		'single_blank_line_before_namespace',
		'single_line_after_imports',
		'single_quote',
		'spaces_before_semicolon',
		'-spaces_cast',
		'standardize_not_equal',
		'-strict',
		'-strict_param',
		'ternary_spaces',
		'trailing_spaces',
		'trim_array_spaces',
		'unused_use',
		'visibility',
		'whitespacy_lines',
	))
	->finder($finder)
;
