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

namespace Eventum\Test\Mail;

use Eventum\Mail\MailMessage;
use Eventum\Test\TestCase;

class LoadEmailTest extends TestCase
{
    public function testLoadBrokenReferences1()
    {
        $raw = $this->readDataFile('kallenote.eml');
        MailMessage::createFromString($raw);
    }
}
