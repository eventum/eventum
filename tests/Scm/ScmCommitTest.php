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
use DB_Helper;
use Eventum\Model\Entity;
use Eventum\Model\Repository\CommitRepository;
use Setup;

/**
 * @group db
 */
class ScmCommitTest extends ScmTestCase
{
    private $changeset;
    private $commit_id;
    private $issue_id = 1;

    public function setUp()
    {
        DB_Helper::getInstance()->query('DELETE FROM `issue_commit` WHERE isc_iss_id=?', [$this->issue_id]);
        $this->createCommit();
    }

    public function createCommit()
    {
        $this->changeset = uniqid('z1', true);
        $ci = Entity\Commit::create()
            ->setScmName('cvs')
            ->setAuthorName('Au Thor')
            ->setCommitDate(Date_Helper::getDateTime())
            ->setChangeset($this->changeset)
            ->setMessage('Mes-Sage');
        $this->commit_id = $ci->save();

        $this->commit_file_id = Entity\CommitFile::create()
            ->setCommitId($ci->getId())
            ->setFilename('file')
            ->save();

        $this->issue_commit_id = Entity\IssueCommit::create()
            ->setCommitId($ci->getId())
            ->setIssueId($this->issue_id)
            ->save();
    }

    public function testGetCommit()
    {
        $c = Entity\Commit::create()->findOneByChangeset($this->changeset);
        $this->assertEquals($this->changeset, $c->getChangeset());
    }

    public function testGetIssueCommits()
    {
        $ic = Entity\IssueCommit::create()->findByIssueId($this->issue_id);
        $this->assertNotNull($ic);
        $this->assertEquals($this->issue_id, $ic[0]->getIssueId());
    }

    public function testFindCommitById()
    {
        $cid = 177966;
        $c = Entity\Commit::create()->findById($cid);
        $this->assertNotNull($c);
        $this->assertEquals($cid, $c->getId());
    }

    public function testIssueCommits()
    {
        $r = new CommitRepository();
        $res = $r->getIssueCommitsArray($this->issue_id);

        $this->assertEquals($this->changeset, $res[0]['com_changeset']);
    }
}
