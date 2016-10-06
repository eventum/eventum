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
}
