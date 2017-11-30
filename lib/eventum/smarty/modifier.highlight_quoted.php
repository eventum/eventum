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

/*
* Smarty plugin
* Type:		modifier
* Name:		highlight_quoted
* Version:	0.1
* Date:		2003-03-13
* Author:	Joscha Feth, joscha@feth.com, www.feth.com
* Purpose:	highlights a typical news message, like:
*		>>>>> Quote level 5
*		>>>> Quote level 4
*		>>> Quote level 3
*		>> Quote level 2
*		> Quote level 1
*		Quote level 0
* Usage:	In the template, use
*		{$text|highlight_quoted}	if you want to color
*						messages beginning with
*						indenters | or >
*						and output with indenter: >
*		or
*		{$text|highlight_quoted:0:"-_"} if the text is indented with - or _ and not escaped
* Params:
*		string	text		the text to highlight
*		int	escape		determines if the HTML special chars
*					in the indented text shall be escaped, default: true
*		string	indenter	the indenter, with which the text
*					shall be indented, default: > and |
*		array	colors		the colors for highlighting the different levels.
*					array is looped through from low to high.
*					default: google groups standard
* Install:	Drop into the plugin directory
*/
function smarty_modifier_highlight_quoted($text, $escape = true, $indenter = '|>', $colors = false)
{
    if (!is_array($colors)) {
        $colors = [];
        $colors[] = '#000000';
        $colors[] = '#0000BB';
        $colors[] = '#681E80';
        $colors[] = '#0070FF';
        $colors[] = '#C67900';
        $colors[] = '#008800';
        $colors[] = '#FF3333';
    }
    $matches = [];
    preg_match_all('/^([ ' . preg_quote($indenter) . ']*)(.*)/m',
                    $text,
                    $matches,
                    PREG_SET_ORDER);
    $ret = '';
    foreach ($matches as $match) {
        if ($escape) {
            $match[2] = htmlspecialchars($match[2]);
        }
        $line = trim($match[2]);
        if ($line !== '') {
            $indent = strlen(preg_replace('/[\s]*/', '', $match[1]));
            $color = $indent % count($colors);
            $ret .= '<font color="' . $colors[$color] . '">';
            $ret .= htmlspecialchars($match[1]) . ' ';
            $ret .= $line;
            $ret .= "</font>\n";
        } else {
            $ret .= "\n";
        }
    }

    return $ret;
}
