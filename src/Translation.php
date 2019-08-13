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

namespace Eventum;

final class Translation
{
    private const CHARSET = 'UTF-8';

    public static function init(string $domain, string $path = null): void
    {
        bindtextdomain($domain, $path);
        bind_textdomain_codeset($domain, self::CHARSET);
        textdomain($domain);
    }

    public static function gettext(string $string, array $args): string
    {
        $translated = gettext($string);

        if ($args) {
            return vsprintf($translated, $args);
        }

        return $translated;
    }

    public static function ngettext(string $string, string $plural, int $number): string
    {
        return ngettext($string, $plural, $number);
    }
}
