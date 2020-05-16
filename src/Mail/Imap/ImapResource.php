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

namespace Eventum\Mail\Imap;

class ImapResource
{
    /** @var resource */
    public $mbox;
    /** @var int */
    public $num;
    /** @var array */
    public $info;
    /** @var object */
    public $imapheaders;
    /** @var object */
    public $overview;
    /** @var array */
    public $headers;
    /** @var string */
    public $content;
}
