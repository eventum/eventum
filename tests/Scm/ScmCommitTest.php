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

    public function setUp()
    {
        $this->issueRepo = Doctrine::getIssueRepository();
        $this->commitRepo = Doctrine::getCommitRepository();
    }

    public function testFindByCommit()
    {
        $commit = $this->createCommit();
        $this->flushCommit($commit);

        $c = $this->commitRepo->findOneByChangeset($commit->getChangeset());
        $this->assertNotNull($c);
        $this->assertEquals($commit->getChangeset(), $c->getChangeset());

        $c = $this->commitRepo->findOneByChangeset('no-such-commit');
        $this->assertNull($c);
    }

    public function testFindCommitsByIssueId()
    {
        $commit = $this->createCommit();
        $this->flushCommit($commit);

        $c = $this->issueRepo->getCommits($commit->getIssue()->getId());
        $this->assertNotEmpty($c);
    }
}
