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

use Date_Helper;
use Eventum\Db\Doctrine;
use Eventum\Model\Entity;
use Eventum\Model\Repository\CommitRepository;
use Eventum\Model\Repository\IssueCommitRepository;
use Setup;

/**
 * @group db
 */
class ScmCommitTest extends ScmTestCase
{
    /** @var \Doctrine\ORM\EntityRepository|IssueCommitRepository */
    private $issueCommitRepo;
    /** @var \Doctrine\ORM\EntityRepository|CommitRepository */
    private $commitRepo;

    private $issue_id = 1;
    private $changeset;
    private $commit_id;
    private $commit_file_id;
    private $issue_commit_id;

    public function setUp()
    {
        $this->issueCommitRepo = Doctrine::getIssueCommitRepository();
        $this->commitRepo = Doctrine::getCommitRepository();

        $this->issueCommitRepo->deleteAllRelations($this->issue_id);
        $this->createCommit();
    }

    /**
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function createCommit()
    {
        $this->changeset = uniqid('z1', false);

        $em = $this->getEntityManager();

        $ci = (new Entity\Commit())
            ->setScmName('cvs')
            ->setAuthorName('Au Thor')
            ->setCommitDate(Date_Helper::getDateTime())
            ->setChangeset($this->changeset)
            ->setMessage('Mes-Sage');
        $em->persist($ci);
        $em->flush();
        $this->commit_id = $ci->getId();

        $cf = (new Entity\CommitFile())
            ->setCommitId($ci->getId())
            ->setFilename('file');
        $em->persist($cf);
        $em->flush();
        $this->commit_file_id = $cf->getId();

        $isc = (new Entity\IssueCommit())
            ->setCommitId($ci->getId())
            ->setIssueId($this->issue_id);
        $em->persist($isc);
        $em->flush();
        $this->issue_commit_id = $isc->getId();
    }

    public function testGetCommit()
    {
        $c = $this->commitRepo->findOneByChangeset($this->changeset);
        $this->assertNotNull($c);
        $this->assertEquals($this->changeset, $c->getChangeset());

        $c = $this->commitRepo->findOneByChangeset('no-such-commit');
        $this->assertNull($c);
    }

    public function testGetIssueCommits()
    {
        $ic = $this->issueCommitRepo->findByIssueId($this->issue_id);
        $this->assertNotNull($ic);
        $this->assertCount(1, $ic);
        $this->assertEquals($this->issue_id, $ic[0]->getIssueId());

        $ic = $this->issueCommitRepo->findByIssueId(-1);
        $this->assertNotNull($ic);
        $this->assertCount(0, $ic);
    }

    public function testFindCommitById()
    {
        $cid = 177966;
        $c = $this->commitRepo->findById($cid);
        $this->assertNotNull($c);
        $this->assertEquals($cid, $c->getId());

        $c = $this->commitRepo->findById(-1);
        $this->assertNull($c);
    }

    public function testIssueCommits()
    {
        $res = $this->commitRepo->getIssueCommitsArray($this->issue_id);

        $this->assertEquals($this->changeset, $res[0]['com_changeset']);
    }
}
