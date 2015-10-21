<?php

class RemoteApiTest extends PHPUnit_Framework_TestCase
{
    const DEBUG = 0;

    private $login = 'admin@example.com';
    private $password = 'admin';

    /** @var RemoteApi */
    private static $client;

    public static function setupBeforeClass()
    {
        $setup = Setup::get();
        if (!isset($setup['tests.xmlrpc_url'])) {
            self::markTestSkipped("tests.xmlrpc_url not set in setup");
        }

        /*
         * 'tests.xmlrpc_url' => 'http://localhost/eventum/rpc/xmlrpc.php',
         */

        $client = new Eventum_RPC($setup['tests.xmlrpc_url']);
        $client->setDebug(self::DEBUG);

        self::$client = $client;
    }

    /**
     * @covers RemoteApi::getDeveloperList
     */
    public function testGetDeveloperList()
    {
        $prj_id = 1;
        $res = self::$client->getDeveloperList($this->login, $this->password, $prj_id);
        $this->assertInternalType('array', $res);
        $this->assertArrayHasKey('Admin User', $res);
        $this->assertEquals('admin@example.com', $res['Admin User']);
    }

    /**
     * @covers RemoteApi::getSimpleIssueDetails
     */
    public function testGetSimpleIssueDetails()
    {
        $issue_id = 1;
        $res = self::$client->getSimpleIssueDetails($this->login, $this->password, $issue_id);
        $this->assertInternalType('array', $res);
        $this->assertArrayHasKey('summary', $res);
        $this->assertArrayHasKey('customer', $res);
        $this->assertArrayHasKey('status', $res);
        $this->assertArrayHasKey('assignments', $res);
        $this->assertArrayHasKey('authorized_names', $res);
    }

    /**
     * @covers RemoteApi::getOpenIssues
     */
    public function testGetOpenIssues()
    {
        $prj_id = 1;
        $show_all_issues = true;
        $status = '';
        $res = self::$client->getOpenIssues($this->login, $this->password, $prj_id, $show_all_issues, $status);

        $this->assertInternalType('array', $res);
        $this->assertArrayHasKey('0', $res);
        $issue = $res[0];

        $this->assertArrayHasKey('issue_id', $issue);
        $this->assertEquals(1, $issue['issue_id']);
        $this->assertArrayHasKey('summary', $issue);
        $this->assertArrayHasKey('assigned_users', $issue);
        $this->assertArrayHasKey('status', $issue);
    }

    /**
     * @covers RemoteApi::isValidLogin
     */
    public function testIsValidLogin()
    {
        $res = self::$client->isValidLogin($this->login, $this->password);
        $this->assertInternalType('string', $res);
        $this->assertEquals('yes', $res);

        $res = self::$client->isValidLogin($this->login . '1', $this->password);
        $this->assertInternalType('string', $res);
        $this->assertEquals('no', $res);

        $res = self::$client->isValidLogin($this->login . '1', $this->password . '1');
        $this->assertInternalType('string', $res);
        $this->assertEquals('no', $res);
    }

    /**
     * @covers RemoteApi::getUserAssignedProjects
     */
    public function testGetUserAssignedProjects()
    {
        $only_customer_projects = false;
        $res = self::$client->getUserAssignedProjects($this->login, $this->password, $only_customer_projects);
        $exp = array(
            array(
                'id'    => '1',
                'title' => 'Default Project',
            ),
        );
        $this->assertEquals($exp, $res);
    }

    /**
     * @covers RemoteApi::getIssueDetails
     */
    public function testGetIssueDetails()
    {
        $issue_id = 1;
        $res = self::$client->getIssueDetails($this->login, $this->password, $issue_id);
        $this->assertInternalType('array', $res);
        $this->assertArrayHasKey('iss_id', $res);
        $this->assertEquals(1, $res['iss_id']);
    }

    /**
     * @covers RemoteApi::getTimeTrackingCategories
     */
    public function testGetTimeTrackingCategories()
    {
        $issue_id = 1;
        $res = self::$client->getTimeTrackingCategories($this->login, $this->password, $issue_id);
        $this->assertInternalType('array', $res);
        $this->assertContains('Tech-Support', $res);
    }

    /**
     * @covers RemoteApi::recordTimeWorked
     */
    public function testRecordTimeWorked()
    {
        $issue_id = 1;
        $cat_id = 1;
        $summary = __FUNCTION__;
        $time_spent = 10;
        $res = self::$client->recordTimeWorked($this->login, $this->password, $issue_id, $cat_id, $summary, $time_spent);

        $this->assertEquals('OK', $res);
    }

    /**
     * @covers RemoteApi::setIssueStatus
     */
    public function testSetIssueStatus()
    {
        $issue_id = 1;
        $new_status = 'implementation';
        $res = self::$client->setIssueStatus($this->login, $this->password, $issue_id, $new_status);

        $this->assertEquals('OK', $res);
    }

    /**
     * @covers RemoteApi::assignIssue
     */
    public function testAssignIssue()
    {
        $issue_id = 1;
        $prj_id = 1;
        $developer = 'admin@example.com';
        $res = self::$client->assignIssue($this->login, $this->password, $issue_id, $prj_id, $developer);

        $this->assertEquals('OK', $res);
    }

    /**
     * @covers RemoteApi::takeIssue
     */
    public function testTakeIssue()
    {
        $issue_id = 1;
        $prj_id = 1;
        try {
            $res = self::$client->takeIssue($this->login, $this->password, $issue_id, $prj_id);
            $this->assertEquals('OK', $res);
        } catch (Exception $e) {
            // already assigned
            $this->assertRegExp('/Issue is currently assigned to/', $e->getMessage());
        }
    }

    /**
     * @covers RemoteApi::addAuthorizedReplier
     */
    public function testAddAuthorizedReplier()
    {
        $issue_id = 1;
        $prj_id = 1;
        $new_replier = 'admin@example.com';
        $res = self::$client->addAuthorizedReplier($this->login, $this->password, $issue_id, $prj_id, $new_replier);

        $this->assertEquals('OK', $res);
    }

    /**
     * @covers RemoteApi::getFileList
     */
    public function testGetFileList()
    {
        $issue_id = 1;

        try {
            $res = self::$client->getFileList($this->login, $this->password, $issue_id);

            $this->assertInternalType('array', $res);
            $this->assertArrayHasKey('0', $res);

            $file = $res[0];
            $this->assertInternalType('array', $file);
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
    public function testGetFile()
    {
        $file_id = 1;

        $res = self::$client->getFile($this->login, $this->password, $file_id);

        $this->assertInternalType('array', $res);
        $this->assertArrayHasKey('iat_id', $res);
        $this->assertArrayHasKey('iat_status', $res);
        $this->assertEquals('internal', $res['iat_status']);
    }

    /**
     * @covers RemoteApi::lookupCustomer
     */
    public function testLookupCustomer()
    {
        $prj_id = 1;
        $field = 'email';
        $value = 'id';

        try {
            $res = self::$client->lookupCustomer($this->login, $this->password, $prj_id, $field, $value);
            $this->assertInternalType('string', $res);
        } catch (Exception $e) {
            $this->assertEquals("Customer Integration not enabled for project $prj_id", $e->getMessage());
        }
    }

    /**
     * FIXME: this doesn't have any sane error checking for invalid status, etc
     *
     * @covers RemoteApi::closeIssue
     */
    public function testCloseIssue()
    {
        $issue_id = 1;
        $new_status = 'closed';
        $resolution_id = 1;
        $send_notification = false;
        $note = __FUNCTION__;

        $res = self::$client->closeIssue($this->login, $this->password, $issue_id, $new_status, $resolution_id, $send_notification, $note);

        $this->assertEquals('OK', $res);
    }

    /**
     * @covers RemoteApi::getClosedAbbreviationAssocList
     */
    public function testGetClosedAbbreviationAssocList()
    {
        $prj_id = 1;
        $res = self::$client->getClosedAbbreviationAssocList($this->login, $this->password, $prj_id);
        $exp = array(
            'REL' => 'released',
            'KIL' => 'killed',
        );
        $this->assertEquals($exp, $res);
    }

    /**
     * @covers RemoteApi::getAbbreviationAssocList
     */
    public function testGetAbbreviationAssocList()
    {
        $prj_id = 1;
        $show_closed = false;
        $res = self::$client->getAbbreviationAssocList($this->login, $this->password, $prj_id, $show_closed);
        $exp = array(
            'DSC' => 'discovery',
            'REQ' => 'requirements',
            'IMP' => 'implementation',
            'TST' => 'evaluation and testing',
        );
        $this->assertEquals($exp, $res);
    }

    /**
     * @covers RemoteApi::getEmailListing
     */
    public function testGetEmailListing()
    {
        $issue_id = 1;
        $res = self::$client->getEmailListing($this->login, $this->password, $issue_id);
        $this->assertInternalType('array', $res);
        $this->assertArrayHasKey('0', $res);

        $email = $res[0];
        $this->assertArrayHasKey('sup_subject', $email);
        $this->assertArrayHasKey('sup_from', $email);
        $this->assertEquals('Admin User', $email['sup_from']);
    }

    /**
     * @covers RemoteApi::getEmail
     */
    public function testGetEmail()
    {
        $issue_id = 1;
        $emai_id = 1;
        try {
            $res = self::$client->getEmail($this->login, $this->password, $issue_id, $emai_id);
        } catch (Exception $e) {

        }
        $this->markTestIncomplete('no test data');
    }

    /**
     * @covers RemoteApi::getNoteListing
     */
    public function testGetNoteListing()
    {
        $issue_id = 1;
        $res = self::$client->getNoteListing($this->login, $this->password, $issue_id);
        $this->assertInternalType('array', $res);
        $this->arrayHasKey('0', $res);

        $note = $res[0];
        $this->assertArrayHasKey('not_id', $note);
        $this->assertArrayHasKey('not_title', $note);
        $this->assertEquals('Issue closed comments', $note['not_title']);
    }

    /**
     * @covers RemoteApi::getNote
     */
    public function testGetNote()
    {
        $issue_id = 1;
        $note_id = 1;
        $res = self::$client->getNote($this->login, $this->password, $issue_id, $note_id);
        $this->assertInternalType('array', $res);

        $this->assertArrayHasKey('not_id', $res);
        $this->assertArrayHasKey('not_title', $res);
        $this->assertEquals('Issue closed comments', $res['not_title']);
    }

    /**
     * @covers RemoteApi::convertNote
     */
    public function testConvertNote()
    {
        $issue_id = 1;
        $note_id = 1;
        $target = 'email';
        $authorize_sender = false;
        try {
            $res = self::$client->convertNote($this->login, $this->password, $issue_id, $note_id, $target, $authorize_sender);
        } catch (Exception $e) {

        }
    }

    /**
     * @covers RemoteApi::mayChangeIssue
     */
    public function testMayChangeIssue()
    {
        $issue_id = 1;
        $res = self::$client->mayChangeIssue($this->login, $this->password, $issue_id);
        $this->assertEquals('no', $res);
    }

    /**
     * @covers RemoteApi::getWeeklyReport
     */
    public function testGetWeeklyReport()
    {
        $week = 1;
        $start = "";
        $end = "";
        $separate_closed = false;
        $res = self::$client->getWeeklyReport($this->login, $this->password, $week, $start, $end, $separate_closed);
        $this->assertRegExp('/Admin User.*Weekly Report/', $res);
    }

    /**
     * @covers RemoteApi::getResolutionAssocList
     */
    public function testGetResolutionAssocList()
    {
        $res = self::$client->getResolutionAssocList();
        $exp = array(
            2 => 'fixed',
            4 => 'unable to reproduce',
            5 => 'not fixable',
            6 => 'duplicate',
            7 => 'not a bug',
            8 => 'suspended',
            9 => "won't fix",
        );
        $this->assertEquals($exp, $res);
    }

    /**
     * @covers RemoteApi::timeClock
     */
    public function testTimeClock()
    {
        $action = 'out';
        $res = self::$client->timeClock($this->login, $this->password, $action);
        $this->assertEquals("admin@example.com successfully clocked out.\n", $res);
    }

    /**
     * @covers RemoteApi::getDraftListing
     */
    public function testGetDraftListing()
    {
        $issue_id = 1;
        $res = self::$client->getDraftListing($this->login, $this->password, $issue_id);
        $this->assertInternalType('array', $res);
        $this->arrayHasKey('0', $res);

        $draft = $res[0];
        $this->assertArrayHasKey('emd_id', $draft);
        $this->assertArrayHasKey('emd_status', $draft);
        $this->assertEquals('pending', $draft['emd_status']);
    }

    /**
     * @covers RemoteApi::getDraft
     */
    public function testGetDraft()
    {
        $issue_id = 1;
        $draft_id = 1;
        $res = self::$client->getDraft($this->login, $this->password, $issue_id, $draft_id);
        $this->assertInternalType('array', $res);
        $this->assertArrayHasKey('emd_id', $res);
        $this->assertArrayHasKey('emd_status', $res);
        $this->assertEquals('pending', $res['emd_status']);
    }

    /**
     * @covers RemoteApi::sendDraft
     */
    public function testSendDraft()
    {
        $issue_id = 1;
        $draft_id = 1;
        try {
            $res = self::$client->sendDraft($this->login, $this->password, $issue_id, $draft_id);
        } catch (Exception $e) {

        }
    }

    /**
     * @covers RemoteApi::redeemIssue
     */
    public function testRedeemIssue()
    {
        $issue_id = 1;
        $types = array();
        try {
            $res = self::$client->redeemIssue($this->login, $this->password, $issue_id, $types);
            $this->assertEquals('OK', $res);
        } catch (Exception $e) {
            $this->assertEquals("No customer integration for issue #1", $e->getMessage());
        }
    }

    /**
     * @covers RemoteApi::unredeemIssue
     */
    public function testUnredeemIssue()
    {
        $issue_id = 1;
        $types = array();
        try {
            $res = self::$client->unredeemIssue($this->login, $this->password, $issue_id, $types);
            $this->assertEquals('OK', $res);
        } catch (Exception $e) {
            $this->assertEquals("No customer integration for issue #1", $e->getMessage());
        }
    }

    /**
     * @covers RemoteApi::getIncidentTypes
     */
    public function testGetIncidentTypes()
    {
        $issue_id = 1;
        $redeemed_only = false;

        try {
            $res = self::$client->getIncidentTypes($this->login, $this->password, $issue_id, $redeemed_only);
        } catch (Exception $e) {
            $this->assertEquals("No customer integration for issue #1", $e->getMessage());
        }
    }

    /**
     * @covers RemoteApi::logCommand
     */
    public function testLogCommand()
    {
        $command = 'hello world';
        $res = self::$client->logCommand($this->login, $this->password, $command);
        $this->assertEquals('OK', $res);
    }
}
