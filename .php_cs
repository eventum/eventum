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
		'linefeed',
		'indentation',
		'trailing_spaces',
		'php_closing_tag',
		'standardize_not_equal',
		'short_tag',
		'ternary_spaces',
		'spaces_cast',
		'object_operator',
		'visibility',
		'return',
		'function_declaration',
		'include',
		'extra_empty_lines',
		'new_with_braces',
		'braces',
		'lowercase_keywords',
		'-short_array_syntax',
		'lowercase_constants',
		'controls_spaces',
		'elseif',
		'eof_ending',
	))
	->finder($finder)
;
