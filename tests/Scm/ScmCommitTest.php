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

namespace Eventum\Test\Scm;

use Eventum\Db\Doctrine;
use Eventum\Model\Entity\Commit;
use Eventum\Model\Repository\CommitRepository;
use Eventum\Model\Repository\IssueRepository;

/**
 * @group db
 */
class ScmCommitTest extends ScmTestCase
{
    /** @var \Doctrine\ORM\EntityRepository|IssueRepository */
    private $issueRepo;
    /** @var \Doctrine\ORM\EntityRepository|CommitRepository */
    private $commitRepo;
    /** @var Commit */
    private $commit;

    public function setUp()
    {
        $this->issueRepo = Doctrine::getIssueRepository();
        $this->commitRepo = Doctrine::getCommitRepository();
        $this->commit = $this->flushCommit($this->createCommit());
    }

    public function testFindByCommit()
    {
        $c = $this->commitRepo->findOneByChangeset($this->commit->getChangeset());
        $this->assertNotNull($c);
        $this->assertEquals($this->commit->getChangeset(), $c->getChangeset());

        $c = $this->commitRepo->findOneByChangeset('no-such-commit');
        $this->assertNull($c);
    }

    public function testFindCommitsByIssueId()
    {
        $c = $this->issueRepo->getCommits($this->commit->getIssue()->getId());
        $this->assertNotEmpty($c, 'can find commits to issue');
    }
}
