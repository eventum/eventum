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

namespace Eventum\Config;

/** @internal */
define('APP_PATH', dirname(__DIR__, 2));

final class Paths
{
    public const APP_PATH = APP_PATH;
    public const APP_PUBLIC_PATH = APP_PATH . '/htdocs';
    public const APP_INC_PATH = APP_PATH . '/lib/eventum';

    // "/var" path for writable data
    private const APP_VAR_PATH = APP_PATH . '/var';
    public const APP_SPOOL_PATH = self::APP_VAR_PATH . '/spool';
    public const APP_CACHE_PATH = self::APP_VAR_PATH . '/cache';

    // define other paths
    public const APP_TPL_PATH = APP_PATH . '/templates';
    public const APP_TPL_COMPILE_PATH = self::APP_CACHE_PATH . '/smarty';
    public const APP_LOG_PATH = self::APP_VAR_PATH . '/log';
    /** @deprecated */
    public const APP_ERROR_LOG = self::APP_LOG_PATH . '/errors.log';
    public const APP_LOCKS_PATH = self::APP_VAR_PATH . '/lock';

    // fonts directory for phplot
    public const APP_FONTS_PATH = APP_PATH . '/vendor/fonts/liberation';
    public const APP_HELP_PATH = APP_PATH . '/docs/help';
}
