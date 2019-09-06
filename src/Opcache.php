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

final class Opcache
{
    /**
     * @see https://www.php.net/opcache_invalidate
     */
    public static function invalidate(string $script, bool $force = true): void
    {
        if (!function_exists('opcache_invalidate') || !filter_var(ini_get('opcache.enable'), FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        opcache_invalidate($script, $force);
    }
}
