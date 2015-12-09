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

/**
 * Test class for Mime_Helper.
 */
class MimeHelperTest extends TestCase
{
    public function testEncodeQuotedPrintable()
    {
        $string = "61.jpg";
        $exp = '=?UTF-8?B?NjEuanBn?=';
        $res = Mime_Helper::encodeQuotedPrintable($string);
        $this->assertEquals($exp, $res, 'do not overflow');

/*
        // avoid any wrapping by specifying line length long enough
        // test = 4
        // : =?ISO-8859-1?B?dGVzdA==?=
        // 3 +2 +10      +3 +7     + 3
        $line_length = strlen($string) * 4 + strlen(APP_CHARSET) + 11;
        echo "ll=$line_length\n";

        #	=?ISO-8859-1?B?a2FtbWliw7xsZXBlYQ==?=glen@wintersunset
        $params = array(
            "scheme" => "Q",
            "input-charset" => APP_CHARSET,
            "output-charset" => APP_CHARSET,
        );
        $string = iconv_mime_encode("", $string, $params);
        echo "HIERO\n";
        #echo $string, "\n";
        echo substr($string, 2), "\n";
        echo "Klaar\n";
*/
    }

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
        return array(
            // iconv test from php manual
            array('=?UTF-8?B?UHLDvGZ1bmcgUHLDvGZ1bmc=?=', 'Prüfung Prüfung'),

            // test that result is returned to APP_CHARSET
            array('=?ISO-8859-1?B?SuTkZ2VybWVpc3Rlcg==?=', 'Jäägermeister'),

            // different charsets inside one string
            array('=?ISO-8859-1?q?M=FCller=2C?= ACME =?US-ASCII?q?Corp=2E?=', 'Müller, ACME Corp.'),

            // bug
            array('Subject: =?iso-8859-15?Q?n=FC=FCd_ei_t=F6=F6ta_adminni_publish_nupp_?=', 'Subject: nüüd ei tööta adminni publish nupp '),
            array('Subject: nüüd ei tööta adminni publish nupp ', 'Subject: nüüd ei tööta adminni publish nupp '),

            // thunderbird test
            array('Subject: =?utf-8?Q?Kas_Teie_tahate_teada,_millele_kulutate_raha_k=C3=B5ige_rohkem=3F?=', 'Subject: Kas Teie tahate teada, millele kulutate raha kõige rohkem?'),
            array('Subject: =?utf-8?Q?Kas_Teie_tahate_teada,_millele_kulutate_raha_k=C3=B5ige_rohkem??=', 'Subject: =?utf-8?Q?Kas_Teie_tahate_teada,_millele_kulutate_raha_k=C3=B5ige_rohkem??='),
        );
    }

    /**
     * Method used to properly quote the sender of a given email address.
     *
     * @access  public
     * @param   string $address The full email address
     * @return  string The properly quoted email address
     */
    public function testQuoteSender()
    {
        $test_data = array(
            '<email@example.org>'   =>  'email@example.org',
            'John Doe <email@example.org>'   =>  '"John Doe" <email@example.org>',
        );
        foreach ($test_data as $string => $exp) {
            $res = Mime_Helper::quoteSender($string);
            $this->assertEquals($exp, $res);
        }
    }

    /**
     * Method used to remove any unnecessary quoting from an email address.
     *
     * @access  public
     * @param   string $address The full email address
     * @return  string The email address without quotes
     */
    function testRemoveQuotes()
    {
        $test_data = array(
            '<email@example.org>'   =>  'email@example.org',
            '"John Doe" <email@example.org>'   =>  'John Doe <email@example.org>',
        );
        foreach ($test_data as $string => $exp) {
            $res = Mime_Helper::removeQuotes($string);
            $this->assertEquals($exp, $res);
        }
    }

    public function testBug901653() {
        $message = file_get_contents(__DIR__.'/data/LP901653.txt');
        $this->assertNotEmpty($message);
        $structure = Mime_Helper::decode($message, true, true);
        $this->assertNotNull($structure);
    }
}
