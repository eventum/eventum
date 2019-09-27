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

use Eventum\Mail\MailMessage;
use Eventum\Test\TestCase;

class HeadersTest extends TestCase
{
    /**
     * @see https://github.com/eventum/eventum/issues/415
     */
    public function test415(): void
    {
        $contents = $this->readDataFile('saved_mail.txt');

        $mail = MailMessage::createFromString($contents);
        $id1 = $mail->messageId;
        $mail->setMessageId('loltrollalla');
        $id2 = $mail->messageId;

        $this->assertEquals('<loltrollalla>', $id2);
        $this->assertNotEquals($id2, $id1);
    }
}
