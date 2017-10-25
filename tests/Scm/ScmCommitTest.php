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

        $cf = (new Entity\CommitFile())
            ->setFilename('file');
        $ci->addFile($cf);

        $em->persist($ci);
        $em->persist($cf);
        $em->flush();

        $this->commit_id = $ci->getId();
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
        $this->createCommit();

        $c = $this->commitRepo->findOneByChangeset($this->changeset);
        $this->assertNotNull($c);
        $this->assertEquals($this->changeset, $c->getChangeset());

        $c = $this->commitRepo->findOneByChangeset('no-such-commit');
        $this->assertNull($c);
    }

    public function testGetIssueCommits()
    {
        $this->createCommit();

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

        $files = iterator_to_array($c->getFiles());
        $this->assertCount(1, $files);
        $this->assertInstanceOf(Entity\CommitFile::class, $files[0]);

        $c = $this->commitRepo->findById(-1);
        $this->assertNull($c);
    }
}
