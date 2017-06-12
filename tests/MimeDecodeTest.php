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
use Setup;

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

    public function testFieldValues()
    {
        $message = $this->readDataFile('bug684922.txt');
        $input = Mime_Helper::decode($message, false, true);
        $mail = MailMessage::createFromString($message);

        $this->assertEquals('"Some Guy" <abcd@origin.com>', $input->headers['from']);
        $this->assertEquals('Some Guy <abcd@origin.com>', $mail->from);
    }

    public function testAddWarningMessage()
    {
        $this->markTestSkipped('requires MailMessage port');
        $setup = Setup::get();
        $setup['email_routing']['status'] = 'enabled';
        $setup['email_routing']['warning']['status'] = 'enabled';

        $issue_id = 1;
        $recipient = 'admin@example.com';
        $body = 'here be dragons';

        // add the warning message to the current message' body, if needed
        $m = MailMessage::createFromHeaderBody([], $body);
        Mail_Helper::addWarningMessage($issue_id, $recipient, $m);
        $fixed_body = $m->getContent();
        $this->assertContains($body, $fixed_body);

        $m = MailMessage::createFromFile(__DIR__ . '/data/attachment-bug.txt');
        Mail_Helper::addWarningMessage($issue_id, $recipient, $m);
        $fixed_body = $m->getContent();
        $this->assertContains('Your reply will be sent to the notification list', $fixed_body);
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
