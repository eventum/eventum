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

namespace Eventum\Test;

use Eventum\Mail\MailMessage;
use Mail_Helper;
use Mime_Helper;

/**
 * Tests Mime_Decode so it could be dropped
 *
 * @see https://github.com/eventum/eventum/pull/256#issuecomment-300879398
 * @see Mime_Helper::decode()
 */
class MimeDecodeTest extends TestCase
{
    public function testEmptyMessage()
    {
        // test empty message
        $message = '';
        $res = Mime_Helper::decode($message, false, true);

        $this->assertMimeHelperResult($res);
    }

    /**
     * Test usecase when Mime_Helper::decode is used only for headers array
     */
    public function testHeaders()
    {
        $message = $this->readfile(__DIR__ . '/data/LP901653.txt');
        $res = Mime_Helper::decode($message, false, true);
        $this->assertMimeHelperResult($res);

        $ph = $res->headers;

        $mail = MailMessage::createFromString($message);
        $zh = $this->pearizeHeaders($mail->getHeadersArray());
        // these headers were manually verified
        unset($ph['from'], $zh['from']);
        unset($ph['to'], $zh['to']);
        unset($ph['content-type'], $zh['content-type']);

        $this->assertEquals($zh, $ph);
    }

    public function testGetMessageID()
    {
        // message-id present
        $mail = MailMessage::createFromFile(__DIR__ . '/data/bug684922.txt');
        $p = Mail_Helper::getMessageID($mail->getHeaders()->toString(), $mail->getContent());
        $z = $mail->MessageId;
        $this->assertEquals($z, $p);

        // message-id not present
        $mail = MailMessage::createFromFile(__DIR__ . '/data/bug684922.txt');
        $p = Mail_Helper::getMessageID($mail->getHeaders()->toString(), $mail->getContent());
        $z = $mail->MessageId;
        $this->assertEquals($z, $p);
    }

    /**
     * Hack out inconsistencies:
     *
     * - pear/mime_decode decodes empty headers as false, zf as ''
     * - pear/mime_decode lowercases headers array keys
     * - zf preserves line continuations
     * - MailMessage helper creates empty Cc: header
     * - zf sanitizes recipient headers
     */
    private function pearizeHeaders($h)
    {
        $headers = [];
        foreach ($h as $k => $v) {
            // strip spaces, irrelevant for test
            if (is_string($v)) {
                $v = preg_replace('/\s+/', ' ', $v);
            }
            if ($v === '') {
                $v = false;
            }
            $headers[strtolower($k)] = $v;
        }

        if ($headers['cc'] == false) {
            unset($headers['cc']);
        }

        return $headers;
    }

    private function assertMimeHelperResult($res)
    {
        $this->assertInstanceOf('stdClass', $res);
        $this->assertInternalType('array', $res->headers);
        $this->assertObjectHasAttribute('headers', $res);
        $this->assertObjectHasAttribute('ctype_primary', $res);
        $this->assertObjectHasAttribute('ctype_secondary', $res);
    }
}
