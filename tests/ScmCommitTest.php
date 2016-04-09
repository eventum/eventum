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

use Eventum\Model\Entity;
use Eventum\Monolog\Logger;

class ScmCommitTest extends TestCase
{
    public static function setUpBeforeClass()
    {
        Logger::initialize();
    }

    public function testCommit()
    {
        $ci = Entity\Commit::create()
            ->setScmName('test1')
            ->setAuthorName('Au Thor')
            ->setCommitDate(Date_Helper::getDateTime())
            ->setCommitId(uniqid('z1'))
            ->setMessage('Mes-Sage');
        $id = $ci->save();
        echo "Created commit: $id\n";

        $id = Entity\CommitFile::create()
            ->setCommitId($ci->getId())
            ->setProjectName('test')
            ->setFilename('file')
            ->save();
        echo "Created commit file: $id\n";

        $id = Entity\IssueCommit::create()
            ->setCommitId($ci->getId())
            ->setIssueId(1)
            ->save();
        echo "Created issue association: $id\n";
    }

    public function testGetCommit()
    {
        $commit_id = 'xl8sgtuo1xRzLW1z';
        $c = Entity\Commit::create()->findOneByCommitId($commit_id);
        $this->assertEquals($commit_id, $c->getCommitId());
    }

    public function testGetIssueCommits()
    {
        $issue_id = 1;
        $ic = Entity\IssueCommit::create()->findByIssueId($issue_id);
        $this->assertNotNull($ic);
        $this->assertEquals($issue_id, $ic[0]->getIssueId());
    }

    public function testFindCommitById()
    {
        $cid = 177966;
        $c = Entity\Commit::create()->findById($cid);
        $this->assertNotNull($c);
        $this->assertEquals($cid, $c[0]->getId());
    }

    public function testIssueCommits()
    {
        $setup = Setup::get();
        $setup['scm']['cvs'] = array(
            'name' => 'cvs',
            'checkout_url' => 'https://localhost/{MODULE}/{FILE}?rev={NEW_VERSION}&content-type=text/x-cvsweb-markup',
            'diff_url' => 'https://localhost/{MODULE}/{FILE}?r1={OLD_VERSION}&r2={NEW_VERSION}&f=h',
            'log_url' => 'https://localhost/{MODULE}/{FILE}?r1={VERSION}#rev{VERSION}',
        );

        $issue_id = 1;
        $r = new \Eventum\Model\Repository\CommitRepository();
        $res = $r->getIssueCommitsArray($issue_id);
        print_r($res);
    }
}
