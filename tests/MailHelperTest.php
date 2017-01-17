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

use Eventum\Mail\Helper\AddressHeader;
use Mail_Helper;

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
        $headers = 'Message-ID: <msgid>';
        $body = 'body';
        $msgid = Mail_Helper::getMessageID($headers, $body);
        $this->assertEquals($exp, $msgid, 'normal msg-id header');

        $headers = 'message-id: <msgid>';
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

    /**
     * test that @see Support::addExtraRecipientsToNotificationList adds cc addresses that are not 7bit
     * but @see Mail_Helper::getEmailAddresses results errors because
     * @see Mail_Helper::fixAddressQuoting encodes just wrong.
     * test the alternative implementation using Zend Mail
     */
    public function getEmailAddresses()
    {
        $addresses = '"Erika Mkaitė" <erika@example.net>, Rööt (Šuperuser) <root@example.org>';

        $res = AddressHeader::fromString($addresses)->getEmails();
        $exp = [
            'erika@example.net',
            'root@example.org',
        ];
        $this->assertEquals($exp, $res);
    }

    /**
     * @param string $description description for the testcase
     * @param string $subject
     * @param string $exp expected result
     * @param bool $remove_issue_id
     * @dataProvider testRemoveExcessReIssueIdData
     */
    public function testRemoveExcessReIssueId($description, $subject, $exp, $remove_issue_id)
    {
        $res = Mail_Helper::RemoveExcessRe($subject, $remove_issue_id);
        $this->assertEquals($exp, $res, $description);
    }

    public function testRemoveExcessReIssueIdData()
    {
        return [
            [
                'no reply prefix',
                '[#123] subject',
                'subject',
                true,
            ],
            [
                'no reply prefix',
                'subject',
                'subject',
                false,
            ],
            [
                're: once',
                're: [#123] subject',
                're: subject',
                true,
            ],
            [
                're: once',
                're: subject',
                're: subject',
                false,
            ],
            [
                're: twice',
                're: re: [#123] subject',
                'Re: subject',
                true,
            ],
            [
                're: twice',
                're: re: subject',
                'Re: subject',
                false,
            ],
            [
                're[2]: with squares',
                're[2]: [#123] re: subject',
                'Re: subject',
                true,
            ],
            [
                're[2]: with squares',
                're[2]: re: subject',
                'Re: subject',
                false,
            ],
            // Italian
            [
                'RIF/rif prefix',
                'RIF: rif: Rif.: subject',
                'Re: subject',
                true,
            ],
        ];
    }

    /**
     * @dataProvider testGetAddressInfoData
     */
    public function testGetAddressInfo($input, $sender_name, $email)
    {
        $res = Mail_Helper::getAddressInfo($input);
        $this->assertEquals($sender_name, $res['sender_name']);
        $this->assertEquals($email, $res['email']);
    }

    public function testGetAddressInfoData()
    {
        return [
            0 => [
                'Test User <test@example.com>',
                '"Test User"',
                'test@example.com',
            ],
            1 => [
                '"Test User" <test@example.com>',
                '"Test User"',
                'test@example.com',
            ],
            2 => [
                '<test@example.com>',
                '',
                'test@example.com',
            ],
            3 => [
                'test@example.com',
                '',
                'test@example.com',
            ],
            4 => [
                '"Test User <test@example.com>" <test@example.com>',
                '"Test User <test@example.com>"',
                'test@example.com',
            ],
        ];
    }

    /**
     * @dataProvider testGetAddressInfoMultipleData
     */
    public function testGetAddressInfoMultiple($input, $exp)
    {
        $res = Mail_Helper::getAddressInfo($input, true);
        $this->assertEquals($exp, $res);
    }

    public function testGetAddressInfoMultipleData()
    {
        return [
            // test for "addressgroup" with empty list
            // see https://github.com/eventum/eventum/issues/91
            1 => [
                'destinatarios-no-revelados: ',
                [],
            ],
            // example taken from RFC822.php class source
            // this doesn't parse correctly, because fixAddressQuoting() breaks it
            // but at least document what it does
            2 => [
                'My Group: "Richard" <richard@localhost> (A comment), ted@example.com (Ted Bloggs), Barney;',
                [
                    [
                        // this is how it currently is parsed
                        'sender_name' => '"My Group: \"Richard"',
                        // this is how it should be parsed if fixAddressQuoting didn't break it
                        //'sender_name' => '"Richard"',
                        'email' => 'richard@localhost',
                        'username' => 'richard',
                        'host' => 'localhost',
                    ],
                    [
                        'sender_name' => '',
                        'email' => 'ted@example.com',
                        'username' => 'ted',
                        'host' => 'example.com',
                    ],
                    [
                        'sender_name' => '',
                        'email' => 'Barney@localhost',
                        'username' => 'Barney',
                        'host' => 'localhost',
                    ],
                ],
            ],
        ];
    }

    /**
     * @param string $input
     * @param string $exp expected result
     * @dataProvider testFormatEmailAddressesData
     */
    public function testFormatEmailAddresses($input, $exp)
    {
        $res = Mail_Helper::formatEmailAddresses($input);
        $this->assertEquals($exp, $res);
    }

    public function testFormatEmailAddressesData()
    {
        return [
            [
                'test@example.com,blah@example.com',
                'test@example.com, blah@example.com',
            ],
            [
                'Test Name <test@example.com>,blah@example.com',
                '"Test Name" <test@example.com>, blah@example.com',
            ],
            [
                '"Bob O\'Reilly" <bob@example.com>,blah@example.com',
                '"Bob O\'Reilly" <bob@example.com>, blah@example.com',
            ],
            ['', ''],
        ];
    }
}
