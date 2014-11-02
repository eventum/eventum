<?php

class RemoteApiTest extends PHPUnit_Framework_TestCase
{
    const DEBUG = 0;

    private $login = 'admin@example.com';
    private $password = 'admin';

    /** @var XML_RPC_Client */
    private static $client;

    public static function setupBeforeClass()
    {
        $setup = Setup::load();
        if (!isset($setup['tests.xmlrpc_url'])) {
            self::markTestSkipped("tests.xmlrpc_url not set in setup");
        }

        /*
         * 'tests.xmlrpc_url' => 'http://localhost/eventum/rpc/xmlrpc.php',
         */
        $url = $setup['tests.xmlrpc_url'];

        $data = parse_url($url);
        if (!isset($data['port'])) {
            $data['port'] = $data['scheme'] == 'https' ? 443 : 80;
        }
        if (!isset($data['path'])) {
            $data['path'] = '';
        }

        $client = new XML_RPC_Client($data['path'], $data['host'], $data['port']);
        $client->setDebug(self::DEBUG);

        self::$client = $client;
    }

    private static function call($method, $args)
    {
        $params = array();
        foreach ($args as $arg) {
            $type = gettype($arg);
            if ($type == 'integer') {
                $type = 'int';
            }
            $params[] = new XML_RPC_Value($arg, $type);
        }
        $msg = new XML_RPC_Message($method, $params);
        $result = self::$client->send($msg);

        if ($result === 0) {
            throw new Exception(self::$client->errstr);
        }
        if (is_object($result) && $result->faultCode()) {
            throw new Exception($result->faultString());
        }

        $value = XML_RPC_decode($result->value());

        return $value;
    }

    /**
     * @covers RemoteApi::getDeveloperList
     */
    public function testGetDeveloperList()
    {
        $prj_id = 1;
        $res = self::call('getDeveloperList', array($this->login, $this->password, $prj_id));
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
        $res = self::call('getSimpleIssueDetails', array($this->login, $this->password, $issue_id));
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
        $res = self::call('getOpenIssues', array($this->login, $this->password, $prj_id, $show_all_issues, $status));

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
        $res = self::call('isValidLogin', array($this->login, $this->password));
        $this->assertInternalType('string', $res);
        $this->assertEquals('yes', $res);

        $res = self::call('isValidLogin', array($this->login . '1', $this->password));
        $this->assertInternalType('string', $res);
        $this->assertEquals('no', $res);

        $res = self::call('isValidLogin', array($this->login . '1', $this->password . '1'));
        $this->assertInternalType('string', $res);
        $this->assertEquals('no', $res);
    }

    /**
     * @covers RemoteApi::getUserAssignedProjects
     */
    public function testGetUserAssignedProjects()
    {
        $only_customer_projects = false;
        $res = self::call('getUserAssignedProjects', array($this->login, $this->password, $only_customer_projects));
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
        $res = self::call('getIssueDetails', array($this->login, $this->password, $issue_id));
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
        $res = self::call('getTimeTrackingCategories', array($this->login, $this->password, $issue_id));
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
        $res = self::call(
            'recordTimeWorked', array($this->login, $this->password, $issue_id, $cat_id, $summary, $time_spent)
        );

        $this->assertEquals('OK', $res);
    }

    /**
     * @covers RemoteApi::setIssueStatus
     */
    public function testSetIssueStatus()
    {
        $issue_id = 1;
        $new_status = 'implementation';
        $res = self::call('setIssueStatus', array($this->login, $this->password, $issue_id, $new_status));

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
        $res = self::call('assignIssue', array($this->login, $this->password, $issue_id, $prj_id, $developer));

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
            $res = self::call('takeIssue', array($this->login, $this->password, $issue_id, $prj_id));
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
        $res = self::call(
            'addAuthorizedReplier', array($this->login, $this->password, $issue_id, $prj_id, $new_replier)
        );

        $this->assertEquals('OK', $res);
    }

    /**
     * @covers RemoteApi::getFileList
     */
    public function testGetFileList()
    {
        $issue_id = 1;

        try {
            $res = self::call('getFileList', array($this->login, $this->password, $issue_id));

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

        $res = self::call('getFile', array($this->login, $this->password, $file_id));

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
            $res = self::call('lookupCustomer', array($this->login, $this->password, $prj_id, $field, $value));
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

        $res = self::call(
            'closeIssue',
            array($this->login, $this->password, $issue_id, $new_status, $resolution_id, $send_notification, $note)
        );

        $this->assertEquals('OK', $res);
    }

    /**
     * @covers RemoteApi::getClosedAbbreviationAssocList
     */
    public function testGetClosedAbbreviationAssocList()
    {
        $res = self::call('getClosedAbbreviationAssocList', array($this->login, $this->password, 1));
        $exp = array(
            'REL' => 'released',
            'KIL' => 'killed',
        );
        $this->assertEquals($exp, $res);
    }

    /**
     * @covers RemoteApi::getAbbreviationAssocList
     * @todo   Implement testGetAbbreviationAssocList().
     */
    public function testGetAbbreviationAssocList()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers RemoteApi::getEmailListing
     * @todo   Implement testGetEmailListing().
     */
    public function testGetEmailListing()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers RemoteApi::getEmail
     * @todo   Implement testGetEmail().
     */
    public function testGetEmail()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers RemoteApi::getNoteListing
     * @todo   Implement testGetNoteListing().
     */
    public function testGetNoteListing()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers RemoteApi::getNote
     * @todo   Implement testGetNote().
     */
    public function testGetNote()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers RemoteApi::convertNote
     * @todo   Implement testConvertNote().
     */
    public function testConvertNote()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers RemoteApi::mayChangeIssue
     * @todo   Implement testMayChangeIssue().
     */
    public function testMayChangeIssue()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers RemoteApi::getWeeklyReport
     * @todo   Implement testGetWeeklyReport().
     */
    public function testGetWeeklyReport()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers RemoteApi::getResolutionAssocList
     * @todo   Implement testGetResolutionAssocList().
     */
    public function testGetResolutionAssocList()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers RemoteApi::timeClock
     * @todo   Implement testTimeClock().
     */
    public function testTimeClock()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers RemoteApi::getDraftListing
     * @todo   Implement testGetDraftListing().
     */
    public function testGetDraftListing()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers RemoteApi::getDraft
     * @todo   Implement testGetDraft().
     */
    public function testGetDraft()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers RemoteApi::sendDraft
     * @todo   Implement testSendDraft().
     */
    public function testSendDraft()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers RemoteApi::redeemIssue
     * @todo   Implement testRedeemIssue().
     */
    public function testRedeemIssue()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers RemoteApi::unredeemIssue
     * @todo   Implement testUnredeemIssue().
     */
    public function testUnredeemIssue()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers RemoteApi::getIncidentTypes
     * @todo   Implement testGetIncidentTypes().
     */
    public function testGetIncidentTypes()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers RemoteApi::logCommand
     * @todo   Implement testLogCommand().
     */
    public function testLogCommand()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }
}
