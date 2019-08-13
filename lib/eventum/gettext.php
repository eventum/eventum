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

use Eventum\Config\Paths;
use Eventum\Translation as t;

function ev_gettext(string $string, ...$args): string
{
    return t::gettext($string, $args);
}

function ev_ngettext($string, $plural, $number): string
{
    return t::ngettext($string, $plural, $number);
}

t::init('eventum', Paths::APP_PATH . '/localization');
