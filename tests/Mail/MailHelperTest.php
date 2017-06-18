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

use Eventum\Mail\Helper\AddressHeader;
use Eventum\Mail\MailMessage;
use Eventum\Test\TestCase;
use Mail_Helper;
use Zend\Mail\Header\HeaderInterface;

/**
 * Test class for Mail_Helper.
 */
class MailHelperTest extends TestCase
{
    public function testGetMessageID()
    {
        $headers = 'x-foo: 1';
        $body = 'body';
        $msgid = MailMessage::createFromHeaderBody($headers, $body)->messageId;
        $exp = '<eventum\.md5\.[0-9a-z]+\.[0-9a-z]+@' . APP_HOSTNAME . '>';
        $this->assertRegExp($exp, $msgid, 'Missing msg-id header');

        $exp = '<msgid>';
        $headers = 'Message-ID: <msgid>';
        $body = 'body';
        $msgid = MailMessage::createFromHeaderBody($headers, $body)->messageId;
        $this->assertEquals($exp, $msgid, 'normal msg-id header');

        $headers = 'message-id: <msgid>';
        $body = 'body';
        $msgid = MailMessage::createFromHeaderBody($headers, $body)->messageId;
        $this->assertEquals($exp, $msgid, 'normal msg-id header (lowercase)');

        // try header continuation \n\t
        $headers = "Message-ID:\n\t<msgid>";
        $body = 'body';
        $msgid = MailMessage::createFromHeaderBody($headers, $body)->messageId;
        $this->assertEquals($exp, $msgid, 'msg-id header with newline');

        // try header continuation \n<space>
        $headers = "Message-ID:\n <msgid>";
        $body = 'body';
        $msgid = MailMessage::createFromHeaderBody($headers, $body)->messageId;
        $this->assertEquals($exp, $msgid, 'msg-id header with newline');

        $headers = "X-Some-header:y\r\nMessage-ID:\n\t<msgid>\nX-Other_header: x";
        $body = 'body';
        $msgid = MailMessage::createFromHeaderBody($headers, $body)->messageId;
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
     *
     * @dataProvider validGetEmailAddressesData
     */
    public function testGetEmailAddresses($input, $exp)
    {
        $res = AddressHeader::fromString($input)->getEmails();
        $this->assertEquals($exp, $res);
    }

    public function validGetEmailAddressesData()
    {
        return [
            [
                // decoded input
                '"Erika Mkaitė" <erika@example.net>, Rööt (Šuperuser) <root@example.org>',
                [
                    'erika@example.net',
                    'root@example.org',
                ],
            ],
            [
                // test that QP encoded input also works
                'Erika =?utf-8?b?TWthaXTElyI=?= <erika@example.net>, =?utf-8?b?UsO2w7Z0IA==?= =?utf-8?b?KMWgdXBlcnVzZXIp?= <root@example.org>',
                [
                    'erika@example.net',
                    'root@example.org',
                ],
            ],
            // the @$array['foo'] results NULL
            [null, []],

            // test comments
            // https://github.com/zendframework/zend-mail/pull/12
            [
                'ted@example.com (Ted Bloggs)',
                ['ted@example.com'],
            ],
            [
                // https://github.com/zendframework/zend-mail/pull/13
                'undisclosed-recipients:;',
                [],
            ],
            [
                // https://github.com/zendframework/zend-mail/pull/13
                // https://github.com/eventum/eventum/issues/91
                'destinatarios-no-revelados:;',
                [],
            ],
        ];
    }

    /**
     * @param string $description description for the testcase
     * @param string $subject
     * @param string $exp expected result
     * @param bool $remove_issue_id
     * @dataProvider RemoveExcessReIssueIdTestData
     */
    public function testRemoveExcessReIssueId($description, $subject, $exp, $remove_issue_id)
    {
        $res = Mail_Helper::RemoveExcessRe($subject, $remove_issue_id);
        $this->assertEquals($exp, $res, $description);
    }

    public function RemoveExcessReIssueIdTestData()
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
     * @dataProvider GetAddressInfoTestData
     */
    public function testGetAddressInfo($input, $sender_name, $email)
    {
        $address = AddressHeader::fromString($input)->getAddress();

        $this->assertEquals($sender_name, $address->getName());
        $this->assertEquals($email, $address->getEmail());
    }

    public function GetAddressInfoTestData()
    {
        return [
            0 => [
                'Test User <test@example.com>',
                'Test User',
                'test@example.com',
            ],
            1 => [
                '"Test User" <test@example.com>',
                'Test User',
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
                'Test User <test@example.com>',
                'test@example.com',
            ],
        ];
    }

    /**
     * @dataProvider GetAddressInfoMultipleTestData
     */
    public function testGetAddressInfoMultiple($input, $exp)
    {
        $res = AddressHeader::fromString($input)->toString();

        // spaces are irrelevant
        $res = preg_replace("/\s+/", ' ', $res);

        $this->assertEquals($exp, $res);
    }

    public function GetAddressInfoMultipleTestData()
    {
        return [
            // test for "addressgroup" with empty list
            // see https://github.com/eventum/eventum/issues/91
            'addressgroup-es' => [
                'destinatarios-no-revelados: ;',
                '',
            ],
            // hostgroup things
            // https://github.com/zendframework/zend-mail/pull/13
            'group-empty' => [
                'undisclosed-recipients:;',
                '',
            ],
            'group-value' => [
                // pear gives:
                // Validation failed for: enemies: john@example.net
                'friends: john@example.com; enemies: john@example.net, bart@example.net;',
                'john@example.com, john@example.net, bart@example.net',
            ],
            // example taken from RFC822.php class source
            // modified because not all of that parsed (and is relevant?)
            'with comment' => [
                'My Group: "Richard" <richard@localhost>, ted@example.com (Ted Bloggs);',
                'Richard <richard@localhost>, ted@example.com',
            ],
        ];
    }

    /**
     * @param string $input
     * @param string $exp expected result
     * @dataProvider FormatEmailAddressesTestData
     */
    public function testFormatEmailAddresses($input, $exp)
    {
        $res = AddressHeader::fromString($input)->toString(HeaderInterface::FORMAT_RAW);
        // spaces are irrelevant
        $res = preg_replace("/\s+/", ' ', $res);
        $this->assertEquals($exp, $res);
    }

    public function FormatEmailAddressesTestData()
    {
        return [
            [
                'test@example.com,blah@example.com',
                'test@example.com, blah@example.com',
            ],
            [
                'Test Name <test@example.com>,blah@example.com',
                'Test Name <test@example.com>, blah@example.com',
            ],
            [
                '"Bob O\'Reilly" <bob@example.com>,blah@example.com',
                'Bob O\'Reilly <bob@example.com>, blah@example.com',
            ],
            ['', ''],
        ];
    }
}
