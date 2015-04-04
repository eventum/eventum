<?php

$finder = Symfony\CS\Finder\DefaultFinder::create()
	->in(__DIR__ . '/cli')
	->in(__DIR__ . '/bin')
	->in(__DIR__ . '/htdocs')
	->in(__DIR__ . '/irc')
	->in(__DIR__ . '/lib/eventum')
	->in(__DIR__ . '/scm')
	->in(__DIR__ . '/upgrade')
	->exclude('pear')
	->exclude('smarty')
;

return Symfony\CS\Config\Config::create()
	->fixers(array(
		'encoding',
		'short_tag',
		'braces',
		'elseif',
		'eof_ending',
		'function_declaration',
		'indentation',
		'linefeed',
		'lowercase_constants',
		'lowercase_keywords',
		'php_closing_tag',
		'trailing_spaces',
		'visibility',
		'extra_empty_lines',
		'include',
		'new_with_braces',
		'object_operator',
		'return',
		'spaces_cast',
		'standardize_not_equal',
		'ternary_spaces',
		'-short_array_syntax',
	))
	->finder($finder)
;
