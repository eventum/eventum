<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright 2011, Elan Ruusamäe <glen@delfi.ee>                        |
// | Copyright (c) 2011 - 2015 Eventum Team.                              |
// +----------------------------------------------------------------------+
// |                                                                      |
// | This program is free software; you can redistribute it and/or modify |
// | it under the terms of the GNU General Public License as published by |
// | the Free Software Foundation; either version 2 of the License, or    |
// | (at your option) any later version.                                  |
// |                                                                      |
// | This program is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
// | GNU General Public License for more details.                         |
// |                                                                      |
// | You should have received a copy of the GNU General Public License    |
// | along with this program; if not, write to:                           |
// |                                                                      |
// | Free Software Foundation, Inc.                                       |
// | 51 Franklin Street, Suite 330                                        |
// | Boston, MA 02110-1301, USA.                                          |
// +----------------------------------------------------------------------+

/**
 * Test class for Mail_Helper.
 */
class MailHelperTest extends TestCase
{
    public function testGetMessageID()
    {

        $headers = '';
        $body = 'body';
        $msgid = Mail_Helper::getMessageID($headers, $body);
        $exp = '<eventum\.md5\.[0-9a-z]+\.[0-9a-z]+@' . APP_HOSTNAME . '>';
        $this->assertRegExp($exp, $msgid, 'Missing msg-id header');

        $exp = '<msgid>';
        $headers = "Message-ID: <msgid>";
        $body = 'body';
        $msgid = Mail_Helper::getMessageID($headers, $body);
        $this->assertEquals($exp, $msgid, 'normal msg-id header');

        $headers = "message-id: <msgid>";
        $body = 'body';
        $msgid = Mail_Helper::getMessageID($headers, $body);
        $this->assertEquals($exp, $msgid, 'normal msg-id header (lowercase)');

        // try header continuation \n\t
        $headers = "Message-ID:\n\t<msgid>";
        $body = 'body';
        $msgid = Mail_Helper::getMessageID($headers, $body);
        $this->assertEquals($exp, $msgid, 'msg-id header with newline');

        // try header continuation \n<space>
        $headers = "Message-ID:\n <msgid>";
        $body = 'body';
        $msgid = Mail_Helper::getMessageID($headers, $body);
        $this->assertEquals($exp, $msgid, 'msg-id header with newline');

        $headers = "X-Some-header:y\r\nMessage-ID:\n\t<msgid>\nX-Other_header: x";
        $body = 'body';
        $msgid = Mail_Helper::getMessageID($headers, $body);
        $this->assertEquals($exp, $msgid, 'msg-id header with newline, following next header');
    }

    public function testGenerateMessageId()
    {
        $msgid = Mail_Helper::generateMessageID();
        // <eventum.md5.54hebbwge.myyt4c@eventum.example.org>
        $exp = '<eventum\.md5\.[0-9a-z]{8,64}\.[0-9a-z]{8,64}@' . APP_HOSTNAME . '>';
        $this->assertRegExp($exp, $msgid, 'Missing msg-id header');
    }

    public function testRemoveExcessReSubjectOnly()
    {
        $subject = 'subject';
        $exp = 'subject';
        $res = Mail_Helper::RemoveExcessRe($subject);
        $this->assertEquals($exp, $res, 'no reply prefix');

        $subject = 're: subject';
        $exp = 're: subject';
        $res = Mail_Helper::RemoveExcessRe($subject);
        $this->assertEquals($exp, $res, 're: once');

        $subject = 're: re: subject';
        $exp = 'Re: subject';
        $res = Mail_Helper::RemoveExcessRe($subject);
        $this->assertEquals($exp, $res, 're: twice');

        $subject = 're[2]: re: subject';
        $exp = 'Re: subject';
        $res = Mail_Helper::RemoveExcessRe($subject);
        $this->assertEquals($exp, $res, 're[2]: with squares');
    }

    public function testRemoveExcessReIssueId()
    {
        $subject = '[#123] subject';
        $exp = 'subject';
        $res = Mail_Helper::RemoveExcessRe($subject, true);
        $this->assertEquals($exp, $res, 'no reply prefix');

        $subject = 're: [#123] subject';
        $exp = 're: subject';
        $res = Mail_Helper::RemoveExcessRe($subject, true);
        $this->assertEquals($exp, $res, 're: once');

        $subject = 're: re: [#123] subject';
        $exp = 'Re: subject';
        $res = Mail_Helper::RemoveExcessRe($subject, true);
        $this->assertEquals($exp, $res, 're: twice');

        $subject = 're[2]: [#123] re: subject';
        $exp = 'Re: subject';
        $res = Mail_Helper::RemoveExcessRe($subject, true);
        $this->assertEquals($exp, $res, 're[2]: with squares');

        // Italian
        $subject = 'RIF: rif: Rif.: subject';
        $exp = 'Re: subject';
        $res = Mail_Helper::RemoveExcessRe($subject);
        $this->assertEquals($exp, $res, 'RIF/rif prefix');
    }

    /**
     * @dataProvider testGetAddressInfo_data
     */
    public function testGetAddressInfo($input, $sender_name, $email)
    {
        $res = Mail_Helper::getAddressInfo($input);
        $this->assertEquals($sender_name, $res['sender_name']);
        $this->assertEquals($email, $res['email']);
    }

    public function testGetAddressInfo_data()
    {
        return array(
            0 => array(
                'Test User <test@example.com>',
                '"Test User"',
                "test@example.com",
            ),
            1 => array(
                '"Test User" <test@example.com>',
                '"Test User"',
                "test@example.com",
            ),
            2 => array(
                '<test@example.com>',
                '',
                "test@example.com",
            ),
            3 => array(
                'test@example.com',
                '',
                "test@example.com",
            ),
            4 => array(
                '"Test User <test@example.com>" <test@example.com>',
                '"Test User <test@example.com>"',
                "test@example.com",
            ),
        );
    }

    /**
     * @dataProvider testGetAddressInfoMultiple_data
     */
    public function testGetAddressInfoMultiple($input, $exp)
    {
        $res = Mail_Helper::getAddressInfo($input, true);
        $this->assertEquals($exp, $res);
    }

    public function testGetAddressInfoMultiple_data()
    {
        return array(
            // test for "addressgroup" with empty list
            // see https://github.com/eventum/eventum/issues/91
            1 => array(
                'destinatarios-no-revelados: ',
                array(),
            ),
            // example taken from RFC822.php class source
            // this doesn't parse correctly, because fixAddressQuoting() breaks it
            // but at least document what it does
            2 => array(
                'My Group: "Richard" <richard@localhost> (A comment), ted@example.com (Ted Bloggs), Barney;',
                array(
                    array(
                        // this is how it currently is parsed
                        'sender_name' => '"My Group: \"Richard"',
                        // this is how it should be parsed if fixAddressQuoting didn't break it
//                        'sender_name' => '"Richard"',
                        'email' => 'richard@localhost',
                        'username' => 'richard',
                        'host' => 'localhost',
                    ),
                    array(
                        'sender_name' => '',
                        'email' => 'ted@example.com',
                        'username' => 'ted',
                        'host' => 'example.com',
                    ),
                    array(
                        'sender_name' => '',
                        'email' => 'Barney@localhost',
                        'username' => 'Barney',
                        'host' => 'localhost',
                    ),
                ),
            ),
        );
    }
}
