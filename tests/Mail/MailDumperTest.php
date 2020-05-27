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

use Eventum\Mail\MailDumper;
use Eventum\Mail\MailMessage;
use Eventum\Test\TestCase;

class MailDumperTest extends TestCase
{
    /**
     * @see https://github.com/eventum/eventum/pull/856
     */
    public function testMailDumper(): void
    {
        $mail = MailMessage::createNew();
        $mail->setContent('test');

        $dumper = new MailDumper(__DIR__ . '/../data/routed_drafts');
        $filename = $dumper->dump($mail);
        $this->assertFileExists($filename);
    }
}
