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

namespace Eventum\Test\Generic;

use Eventum\RPC\EventumXmlRpcClient;
use Eventum\RPC\RemoteApi;
use Eventum\ServiceContainer;
use Eventum\Test\TestCase;
use Exception;

/**
 * @group api
 */
class RemoteApiTest extends TestCase
{
    /**
     * @see \PhpXmlRpc\Client::setDebug
     */
    private const APICLIENT_DEBUG = 0;

    /** @var RemoteApi */
    private static $client;

    public static function setupBeforeClass(): void
    {
        $setup = ServiceContainer::getConfig();
        if (!isset($setup['tests.xmlrpc_url'])) {
            self::markTestSkipped('tests.xmlrpc_url not set in setup');
        }

        /*
         * 'tests.xmlrpc_url' => 'http://localhost/eventum/rpc/xmlrpc.php',
         * 'tests.xmlrpc_login' => 'admin@example.com',
         * 'tests.xmlrpc_token' => 'admin',
         */

        $client = new EventumXmlRpcClient($setup['tests.xmlrpc_url']);
        $client->setCredentials($setup['tests.xmlrpc_login'], $setup['tests.xmlrpc_token']);
        $client->setDebug(self::APICLIENT_DEBUG);

        self::$client = $client;
    }

    /**
     * @covers RemoteApi::getDeveloperList
     */
    public function testGetDeveloperList(): void
    {
        $prj_id = 1;
        $res = self::$client->getDeveloperList($prj_id);
        $this->assertIsArray($res);
        $this->assertArrayHasKey('Admin User', $res);
        $this->assertEquals('admin@example.com', $res['Admin User']);
    }

    /**
     * @covers RemoteApi::getSimpleIssueDetails
     */
    public function testGetSimpleIssueDetails(): void
    {
        $issue_id = 1;
        $res = self::$client->getSimpleIssueDetails($issue_id);
        $this->assertIsArray($res);
        $this->assertArrayHasKey('summary', $res);
        $this->assertArrayHasKey('customer', $res);
        $this->assertArrayHasKey('status', $res);
        $this->assertArrayHasKey('assignments', $res);
        $this->assertArrayHasKey('authorized_names', $res);
    }

    /**
     * @covers RemoteApi::getOpenIssues
     */
    public function testGetOpenIssues(): void
    {
        $prj_id = 1;
        $show_all_issues = true;
        $status = '';
        $res = self::$client->getOpenIssues($prj_id, $show_all_issues, $status);

        $this->assertIsArray($res);
        $this->assertArrayHasKey('0', $res);
        $issue = $res[0];

        $this->assertArrayHasKey('issue_id', $issue);
        $this->assertEquals(1, $issue['issue_id']);
        $this->assertArrayHasKey('summary', $issue);
        $this->assertArrayHasKey('assigned_users', $issue);
        $this->assertArrayHasKey('status', $issue);
    }

    /**
     * @covers RemoteApi::getUserAssignedProjects
     */
    public function testGetUserAssignedProjects(): void
    {
        $only_customer_projects = false;
        $res = self::$client->getUserAssignedProjects($only_customer_projects);
        $exp = [
            [
                'id' => '1',
                'title' => 'Default Project',
            ],
        ];
        $this->assertEquals($exp, $res);
    }

    /**
     * @covers RemoteApi::getIssueDetails
     */
    public function testGetIssueDetails(): void
    {
        $issue_id = 1;
        $res = self::$client->getIssueDetails($issue_id);
        $this->assertIsArray($res);
        $this->assertArrayHasKey('iss_id', $res);
        $this->assertEquals(1, $res['iss_id']);
    }

    /**
     * @covers RemoteApi::getTimeTrackingCategories
     */
    public function testGetTimeTrackingCategories(): void
    {
        $issue_id = 1;
        $res = self::$client->getTimeTrackingCategories($issue_id);
        $this->assertIsArray($res);
        $this->assertContains('Tech-Support', $res);
    }

    /**
     * @covers RemoteApi::recordTimeWorked
     */
    public function testRecordTimeWorked(): void
    {
        $issue_id = 1;
        $cat_id = 1;
        $summary = __FUNCTION__;
        $time_spent = 10;
        $res = self::$client->recordTimeWorked($issue_id, $cat_id, $summary, $time_spent);

        $this->assertEquals('OK', $res);
    }

    /**
     * @covers RemoteApi::setIssueStatus
     */
    public function testSetIssueStatus(): void
    {
        $issue_id = 1;
        $new_status = 'implementation';
        $res = self::$client->setIssueStatus($issue_id, $new_status);

        $this->assertEquals('OK', $res);
    }

    /**
     * @covers RemoteApi::assignIssue
     */
    public function testAssignIssue(): void
    {
        $issue_id = 1;
        $prj_id = 1;
        $developer = 'admin@example.com';
        $res = self::$client->assignIssue($issue_id, $prj_id, $developer);

        $this->assertEquals('OK', $res);
    }

    /**
     * @covers RemoteApi::takeIssue
     */
    public function testTakeIssue(): void
    {
        $issue_id = 1;
        $prj_id = 1;
        try {
            $res = self::$client->takeIssue($issue_id, $prj_id);
            $this->assertEquals('OK', $res);
        } catch (Exception $e) {
            // already assigned
            $this->assertRegExp('/Issue is currently assigned to/', $e->getMessage());
        }
    }

    /**
     * @covers RemoteApi::addAuthorizedReplier
     */
    public function testAddAuthorizedReplier(): void
    {
        $issue_id = 1;
        $prj_id = 1;
        $new_replier = 'admin@example.com';
        $res = self::$client->addAuthorizedReplier($issue_id, $prj_id, $new_replier);

        $this->assertEquals('OK', $res);
    }

    /**
     * @covers RemoteApi::getFileList
     */
    public function testGetFileList(): void
    {
        $issue_id = 1;

        try {
            $res = self::$client->getFileList($issue_id);

            $this->assertIsArray($res);
            $this->assertArrayHasKey('0', $res);

            $file = $res[0];
            $this->assertIsArray($file);
            $this->assertArrayHasKey('iat_id', $file);
            $this->assertArrayHasKey('iat_status', $file);
            $this->assertEquals('internal', $file['iat_status']);
        } catch (Exception $e) {
            $this->assertEquals('No files could be found', $e->getMessage());
        }
    }

    /**
     * @covers RemoteApi::getFile
     */
    public function testGetFile(): void
    {
        $file_id = 1;

        $res = self::$client->getFile($file_id);

        $this->assertIsArray($res);
        $this->assertArrayHasKey('iat_id', $res);
        $this->assertArrayHasKey('iat_status', $res);
        $this->assertEquals('internal', $res['iat_status']);
    }

    /**
     * @covers RemoteApi::lookupCustomer
     */
    public function testLookupCustomer(): void
    {
        $prj_id = 1;
        $field = 'email';
        $value = 'id';

        try {
            $res = self::$client->lookupCustomer($prj_id, $field, $value);
            $this->assertIsString($res);
        } catch (Exception $e) {
            $this->assertEquals("Customer Integration not enabled for project $prj_id", $e->getMessage());
        }
    }

    /**
     * FIXME: this doesn't have any sane error checking for invalid status, etc
     *
     * @covers RemoteApi::closeIssue
     */
    public function testCloseIssue(): void
    {
        $issue_id = 1;
        $new_status = 'closed';
        $resolution_id = 1;
        $send_notification = false;
        $note = __FUNCTION__;

        $res = self::$client->closeIssue($issue_id, $new_status, $resolution_id, $send_notification, $note);

        $this->assertEquals('OK', $res);
    }

    /**
     * @covers RemoteApi::getClosedAbbreviationAssocList
     */
    public function testGetClosedAbbreviationAssocList(): void
    {
        $prj_id = 1;
        $res = self::$client->getClosedAbbreviationAssocList($prj_id);
        $exp = [
            'REL' => 'released',
            'KIL' => 'killed',
        ];
        $this->assertEquals($exp, $res);
    }

    /**
     * @covers RemoteApi::getAbbreviationAssocList
     */
    public function testGetAbbreviationAssocList(): void
    {
        $prj_id = 1;
        $show_closed = false;
        $res = self::$client->getAbbreviationAssocList($prj_id, $show_closed);
        $exp = [
            'DSC' => 'discovery',
            'REQ' => 'requirements',
            'IMP' => 'implementation',
            'TST' => 'evaluation and testing',
        ];
        $this->assertEquals($exp, $res);
    }

    /**
     * @covers RemoteApi::getEmailListing
     */
    public function testGetEmailListing(): void
    {
        $issue_id = 1;
        $res = self::$client->getEmailListing($issue_id);
        $this->assertIsArray($res);
        $this->assertArrayHasKey('0', $res);

        $email = $res[0];
        $this->assertArrayHasKey('sup_subject', $email);
        $this->assertArrayHasKey('sup_from', $email);
        $this->assertEquals('Admin User', $email['sup_from']);
    }

    /**
     * @covers RemoteApi::getEmail
     */
    public function testGetEmail(): void
    {
        $issue_id = 1;
        $emai_id = 1;
        try {
            $res = self::$client->getEmail($issue_id, $emai_id);
        } catch (Exception $e) {
        }
        $this->markTestIncomplete('no test data');
    }

    /**
     * @covers RemoteApi::getNoteListing
     */
    public function testGetNoteListing(): void
    {
        $issue_id = 1;
        $res = self::$client->getNoteListing($issue_id);
        $this->assertIsArray($res);
        $this->arrayHasKey('0', $res);

        $note = $res[0];
        $this->assertArrayHasKey('not_id', $note);
        $this->assertArrayHasKey('not_title', $note);
        $this->assertEquals('Issue closed comments', $note['not_title']);
    }

    /**
     * @covers RemoteApi::getNote
     */
    public function testGetNote(): void
    {
        $issue_id = 1;
        $note_id = 1;
        $res = self::$client->getNote($issue_id, $note_id);
        $this->assertIsArray($res);

        $this->assertArrayHasKey('not_id', $res);
        $this->assertArrayHasKey('not_title', $res);
        $this->assertEquals('Issue closed comments', $res['not_title']);
    }

    /**
     * @covers RemoteApi::convertNote
     */
    public function testConvertNote(): void
    {
        $issue_id = 1;
        $note_id = 1;
        $target = 'email';
        $authorize_sender = false;
        try {
            $res = self::$client->convertNote($issue_id, $note_id, $target, $authorize_sender);
        } catch (Exception $e) {
        }
    }

    /**
     * @covers RemoteApi::mayChangeIssue
     */
    public function testMayChangeIssue(): void
    {
        $issue_id = 1;
        $res = self::$client->mayChangeIssue($issue_id);
        $this->assertEquals('no', $res);
    }

    /**
     * @covers RemoteApi::getWeeklyReport
     */
    public function testGetWeeklyReport(): void
    {
        $week = 1;
        $start = '';
        $end = '';
        $separate_closed = false;
        $res = self::$client->getWeeklyReport($week, $start, $end, $separate_closed);
        $this->assertRegExp('/Admin User.*Weekly Report/', $res);
    }

    /**
     * @covers RemoteApi::getResolutionAssocList
     */
    public function testGetResolutionAssocList(): void
    {
        $res = self::$client->getResolutionAssocList();
        $exp = [
            2 => 'fixed',
            4 => 'unable to reproduce',
            5 => 'not fixable',
            6 => 'duplicate',
            7 => 'not a bug',
            8 => 'suspended',
            9 => "won't fix",
        ];
        $this->assertEquals($exp, $res);
    }

    /**
     * @covers RemoteApi::timeClock
     */
    public function testTimeClock(): void
    {
        $action = 'out';
        $res = self::$client->timeClock($action);
        $this->assertEquals("admin@example.com successfully clocked out.\n", $res);
    }

    /**
     * @covers RemoteApi::getDraftListing
     */
    public function testGetDraftListing(): void
    {
        $issue_id = 1;
        $res = self::$client->getDraftListing($issue_id);
        $this->assertIsArray($res);
        $this->arrayHasKey('0', $res);

        $draft = $res[0];
        $this->assertArrayHasKey('emd_id', $draft);
        $this->assertArrayHasKey('emd_status', $draft);
        $this->assertEquals('pending', $draft['emd_status']);
    }

    /**
     * @covers RemoteApi::getDraft
     */
    public function testGetDraft(): void
    {
        $issue_id = 1;
        $draft_id = 1;
        $res = self::$client->getDraft($issue_id, $draft_id);
        $this->assertIsArray($res);
        $this->assertArrayHasKey('emd_id', $res);
        $this->assertArrayHasKey('emd_status', $res);
        $this->assertEquals('pending', $res['emd_status']);
    }

    /**
     * @covers RemoteApi::sendDraft
     */
    public function testSendDraft(): void
    {
        $issue_id = 1;
        $draft_id = 1;
        try {
            $res = self::$client->sendDraft($issue_id, $draft_id);
        } catch (Exception $e) {
        }
    }

    /**
     * @covers RemoteApi::redeemIssue
     */
    public function testRedeemIssue(): void
    {
        $issue_id = 1;
        $types = [];
        try {
            $res = self::$client->redeemIssue($issue_id, $types);
            $this->assertEquals('OK', $res);
        } catch (Exception $e) {
            $this->assertEquals('No customer integration for issue #1', $e->getMessage());
        }
    }

    /**
     * @covers RemoteApi::unredeemIssue
     */
    public function testUnredeemIssue(): void
    {
        $issue_id = 1;
        $types = [];
        try {
            $res = self::$client->unredeemIssue($issue_id, $types);
            $this->assertEquals('OK', $res);
        } catch (Exception $e) {
            $this->assertEquals('No customer integration for issue #1', $e->getMessage());
        }
    }

    /**
     * @covers RemoteApi::getIncidentTypes
     */
    public function testGetIncidentTypes(): void
    {
        $issue_id = 1;
        $redeemed_only = false;

        try {
            self::$client->getIncidentTypes($issue_id, $redeemed_only);
        } catch (Exception $e) {
            $this->assertEquals('No customer integration for issue #1', $e->getMessage());
        }
    }

    /**
     * @covers RemoteApi::logCommand
     */
    public function testLogCommand(): void
    {
        $command = 'hello world';
        $res = self::$client->logCommand($command);
        $this->assertEquals('OK', $res);
    }
}
