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
use Mime_Helper;

/**
 * @group mail
 */
class MimeHelperTest extends TestCase
{
    /**
     * @dataProvider dataDecodeQuotedPrintable
     */
    public function testDecodeQuotedPrintable($str, $exp)
    {
        $res = Mime_Helper::decodeQuotedPrintable($str);
        $this->assertEquals($exp, $res);
    }

    public function dataDecodeQuotedPrintable()
    {
        return [
            // iconv test from php manual
            ['=?UTF-8?B?UHLDvGZ1bmcgUHLDvGZ1bmc=?=', 'Prüfung Prüfung'],

            // test that result is returned to APP_CHARSET
            ['=?ISO-8859-1?B?SuTkZ2VybWVpc3Rlcg==?=', 'Jäägermeister'],

            // different charsets inside one string
            ['=?ISO-8859-1?q?M=FCller=2C?= ACME =?US-ASCII?q?Corp=2E?=', 'Müller, ACME Corp.'],

            // bug
            ['Subject: =?iso-8859-15?Q?n=FC=FCd_ei_t=F6=F6ta_adminni_publish_nupp_?=', 'Subject: nüüd ei tööta adminni publish nupp '],
            ['Subject: nüüd ei tööta adminni publish nupp ', 'Subject: nüüd ei tööta adminni publish nupp '],

            // thunderbird test
            ['Subject: =?utf-8?Q?Kas_Teie_tahate_teada,_millele_kulutate_raha_k=C3=B5ige_rohkem=3F?=', 'Subject: Kas Teie tahate teada, millele kulutate raha kõige rohkem?'],
            ['Subject: =?utf-8?Q?Kas_Teie_tahate_teada,_millele_kulutate_raha_k=C3=B5ige_rohkem??=', 'Subject: =?utf-8?Q?Kas_Teie_tahate_teada,_millele_kulutate_raha_k=C3=B5ige_rohkem??='],
        ];
    }

    /**
     * Method used to properly quote the sender of a given email address.
     */
    public function testQuoteSender()
    {
        $test_data = [
            '<email@example.org>' => 'email@example.org',
            'John Doe <email@example.org>' => '"John Doe" <email@example.org>',
        ];
        foreach ($test_data as $string => $exp) {
            $res = Mime_Helper::quoteSender($string);
            $this->assertEquals($exp, $res);
        }
    }

    /**
     * Method used to remove any unnecessary quoting from an email address.
     */
    public function testRemoveQuotes()
    {
        $test_data = [
            '<email@example.org>' => 'email@example.org',
            '"John Doe" <email@example.org>' => 'John Doe <email@example.org>',
        ];
        foreach ($test_data as $string => $exp) {
            $res = Mime_Helper::removeQuotes($string);
            $this->assertEquals($exp, $res);
        }
    }

    public function testBug901653()
    {
        $message = $this->readDataFile('LP901653.txt');
        $mail = MailMessage::createFromString($message);
        $this->assertNotNull($mail);
    }
}
