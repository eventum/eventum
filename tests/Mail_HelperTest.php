<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright 2011, Elan Ruusamäe <glen@delfi.ee>                        |
// | Copyright (c) 2011 - 2012 Eventum Team.                              |
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
// | 59 Temple Place - Suite 330                                          |
// | Boston, MA 02111-1307, USA.                                          |
// +----------------------------------------------------------------------+

require_once 'PHPUnit/Framework.php';
require_once 'TestSetup.php';

/**
 * Test class for Mail_Helper.
 */
class Mail_HelperTest extends PHPUnit_Framework_TestCase
{
    public function testGetMessageID()
    {

        $headers = '';
        $body = 'body';
        $msgid = Mail_Helper::getMessageID($headers, $body);
        $exp = '<eventum\.md5\.[0-9a-z]+\.[0-9a-z]+@'.APP_HOSTNAME.'>';
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
}
