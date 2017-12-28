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

    /**
     * @param string $content
     * @return MimePart
     */
    public static function createTextPart($content)
    {
        return self::create($content, Mime\Mime::TYPE_TEXT);
    }

    /**
     * @param string $content
     * @param string $type
     * @param string $filename
     * @return Mime\Part
     */
    public static function createAttachmentPart($content, $type, $filename)
    {
        return self::create($content, $type)
            ->setDisposition(Mime\Mime::DISPOSITION_ATTACHMENT)
            ->setFileName($filename);
    }
}
