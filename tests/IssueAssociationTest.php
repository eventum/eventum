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

use Eventum\Model\Repository\IssueAssociationRepository;
use Eventum\Monolog\Logger;

class IssueAssociation extends TestCase
{
    public static function setUpBeforeClass()
    {
        Logger::initialize();
    }

    public function setUp()
    {
        $db = DB_Helper::getInstance();
        $issues = '12,13,14,15';
        $db->query(
            "delete FROM {{%issue_association}} WHERE isa_issue_id in ($issues) OR isa_associated_id in($issues)"
        );
    }

    public function testAssociateIssue()
    {
        $usr_id = APP_SYSTEM_USER_ID;
        $iss1_id = 12;
        $iss2_id = 13;

        $repo = IssueAssociationRepository::create();

        $repo->addIssueAssociation($usr_id, $iss1_id, $iss2_id);
        $assoc1 = $repo->getAssociatedIssues($iss1_id);
        $assoc2 = $repo->getAssociatedIssues($iss2_id);

        // the association exists both ways
        $this->assertEquals([$iss2_id], $assoc1);
        $this->assertEquals([$iss1_id], $assoc2);

        // adding association again throws
        try {
            $repo->addIssueAssociation($usr_id, $iss1_id, $iss2_id);
            $this->fail();
        } catch (InvalidArgumentException $e) {
            $this->assertEquals("Issue $iss1_id already associated to $iss2_id", $e->getMessage());
        }
        try {
            $repo->addIssueAssociation($usr_id, $iss2_id, $iss1_id);
            $this->fail();
        } catch (InvalidArgumentException $e) {
            $this->assertEquals("Issue $iss2_id already associated to $iss1_id", $e->getMessage());
        }

        // now remove the association
        $repo->removeAssociation($usr_id, $iss1_id, $iss2_id);
        // second remove should fail both sides
        try {
            $repo->removeAssociation($usr_id, $iss1_id, $iss2_id);
            $this->fail();
        } catch (InvalidArgumentException $e) {
            $this->assertEquals("Issue $iss1_id not associated to $iss2_id", $e->getMessage());
        }
        try {
            $repo->removeAssociation($usr_id, $iss2_id, $iss1_id);
            $this->fail();
        } catch (InvalidArgumentException $e) {
            $this->assertEquals("Issue $iss2_id not associated to $iss1_id", $e->getMessage());
        }
    }

    public function testBulkUpdate()
    {
        $usr_id = APP_SYSTEM_USER_ID;
        $issue_id = 12;
        $repo = IssueAssociationRepository::create();

        $associated_issues = [$issue_id, '13', '14', 15, $issue_id, 13, 'lol', -1, null, '', false];
        $res = $repo->updateAssociations($usr_id, $issue_id, $associated_issues);
        // no "issue does not exist errors" expected
        $this->assertEmpty($res);

        $res = $repo->getAssociatedIssues($issue_id);
        $exp = [13, 14, 15];
        $this->assertEquals($exp, $res);

        // test that removing also works
        $associated_issues = ['13'];
        $res = $repo->updateAssociations($usr_id, $issue_id, $associated_issues);
        $this->assertEmpty($res);
        $res = $repo->getAssociatedIssues($issue_id);
        $exp = [13];
        $this->assertEquals($exp, $res);
    }

    /**
     * @test Issue::getAssociatedIssuesDetails();
     * @test Issue::getAssociatedIssues();
     */
    public function testGetDetails()
    {
        $usr_id = APP_SYSTEM_USER_ID;
        $iss1_id = 12;
        $iss2_id = 13;

        $repo = IssueAssociationRepository::create();
        $repo->addIssueAssociation($usr_id, $iss1_id, $iss2_id);

        // direct view
        $associated_issues = $repo->getAssociatedIssues($iss1_id);
        $associated_issues_details = $repo->getIssueDetails($associated_issues);

        $this->assertEquals([$iss2_id], $associated_issues);
        // array(
        //  'associated_issue' => '13',
        //  'associated_title' => '',
        //  'current_status' => 'discovery',
        // 'is_closed' => '0',
        // ),
        $this->assertEquals($iss2_id, $associated_issues_details[0]['associated_issue']);

        // reverse view
        $associated_issues = $repo->getAssociatedIssues($iss2_id);
        $associated_issues_details = $repo->getIssueDetails($associated_issues);

        $this->assertEquals([$iss1_id], $associated_issues);
        // array(
        //  'associated_issue' => '13',
        //  'associated_title' => '',
        //  'current_status' => 'discovery',
        // 'is_closed' => '0',
        // ),
        $this->assertEquals($iss1_id, $associated_issues_details[0]['associated_issue']);
    }
}
