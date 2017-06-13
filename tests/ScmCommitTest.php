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

namespace Eventum\Test;

use Date_Helper;
use DB_Helper;
use Eventum\Model\Entity;
use Eventum\Model\Repository\CommitRepository;
use Eventum\Monolog\Logger;
use Eventum\Scm\Adapter\GitlabScm;
use Setup;
use Symfony\Component\HttpFoundation\Request;

/**
 * @group db
 */
class ScmCommitTest extends TestCase
{
    private $changeset;
    private $commit_id;
    private $issue_id = 1;

    public function setUp()
    {
        $scm = [
            'cvs' => [
                'name' => 'cvs',
                'checkout_url' => 'https://localhost/{MODULE}/{FILE}?rev={NEW_VERSION}&content-type=text/x-cvsweb-markup',
                'diff_url' => 'https://localhost/{MODULE}/{FILE}?r1={OLD_VERSION}&r2={NEW_VERSION}&f=h',
                'log_url' => 'https://localhost/{MODULE}/{FILE}?r1={VERSION}#rev{VERSION}',
            ],
            'gitlab' => [
                'name' => 'gitlab',
                'urls' => [
                    'http://localhost:10080',
                    'git@localhost',
                ],
                'only' => ['merge-tip'],
                'except' => ['dev'],
                'checkout_url' => 'http://localhost:10080/{PROJECT}/blob/{VERSION}/{FILE}',
                'diff_url' => 'http://localhost:10080/{PROJECT}/commit/{VERSION}#{FILE}',
                'log_url' => 'http://localhost:10080/{PROJECT}/commits/{VERSION}/{FILE}',
            ],
        ];
        Setup::set(['scm' => $scm]);

        DB_Helper::getInstance()->query('DELETE FROM {{%issue_commit}} WHERE isc_iss_id=?', [$this->issue_id]);
        $this->createCommit();
    }

    public function createCommit()
    {
        $this->changeset = uniqid('z1');
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

    /**
     * Test commit push over Api
     */
    public function testGitlabCommitApi()
    {
        $api_url = $this->getCommitUrl();
        $payload = file_get_contents(__DIR__ . '/data/gitlab-commit.json');

        $request = Request::create($api_url, 'POST', [], [], [], [], $payload);
        $request->headers->set(GitlabScm::GITLAB_HEADER, 'Push Hook');

        $logger = Logger::app();
        $handler = new GitlabScm($request, $logger);
        $this->assertTrue($handler->can());

        $handler->process();
    }

    /**
     * Test commit push over http
     */
    public function testGitlabCommitUrl()
    {
        $api_url = $this->getCommitUrl();

        $payload = __DIR__ . '/data/gitlab-commit.json';
        $headers = "-H 'X-Gitlab-Event: Push Hook'";
        $this->POST($api_url, $payload, $headers);
    }

    private function getCommitUrl()
    {
        $setup = Setup::get();
        $api_url = $setup['tests.commit_url'];
        $this->assertNotNull($api_url);

        return $api_url;
    }

    private function POST($url, $payload, $headers = '')
    {
        return shell_exec("curl -Ss $headers -X POST --data @{$payload} {$url}");
    }
}
