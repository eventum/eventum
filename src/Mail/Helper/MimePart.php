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

namespace Eventum\Mail\Helper;

use Zend\Mime;

class MimePart extends Mime\Part
{
    const CHARSET = APP_CHARSET;

    public static function create($content, $type, $charset = self::CHARSET)
    {
        $part = new self($content);
        $part->type = $type;
        $part->charset = $charset;

        return $part;
    }

    public static function createTextPart($content)
    {
        return self::create($content, Mime\Mime::TYPE_TEXT);
    }
}
