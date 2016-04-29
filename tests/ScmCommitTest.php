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
use Eventum\Scm\Adapter\GitlabScm;
use Symfony\Component\HttpFoundation\Request;

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
            ->setChangeset(uniqid('z1'))
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
        $c = Entity\Commit::create()->findOneByChangeset($commit_id);
        $this->assertEquals($commit_id, $c->getChangeset());
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
        $this->assertEquals($cid, $c->getId());
    }

    public function testIssueCommits()
    {
        $setup = Setup::get();
        $setup['scm']['cvs'] = [
            'name' => 'cvs',
            'checkout_url' => 'https://localhost/{MODULE}/{FILE}?rev={NEW_VERSION}&content-type=text/x-cvsweb-markup',
            'diff_url' => 'https://localhost/{MODULE}/{FILE}?r1={OLD_VERSION}&r2={NEW_VERSION}&f=h',
            'log_url' => 'https://localhost/{MODULE}/{FILE}?r1={VERSION}#rev{VERSION}',
        ];

        $issue_id = 1;
        $r = new \Eventum\Model\Repository\CommitRepository();
        $res = $r->getIssueCommitsArray($issue_id);
        print_r($res);
    }

    /**
     * Test commit push over Api
     */
    public function testGitlabCommitApi()
    {
        $api_url = $this->getCommitUrl();
        $payload = file_get_contents(__DIR__ . '/data/gitlab-commit.json');

        $request = Request::create($api_url, 'GET', [], [], [], [], $payload);
        $request->headers->set(GitlabScm::GITLAB_HEADER, 'Push Hook');

        // configure
        $setup = Setup::get();
        $setup['scm']['gitlab'] = [
            'name' => 'gitlab',
            'urls' => [
                'http://localhost:10080',
                'git@localhost',
            ],
            'only' => [],
            'except' => ['dev'],
            'checkout_url' => 'http://localhost:10080/{PROJECT}/blob/{VERSION}/{FILE}',
            'diff_url' => 'http://localhost:10080/{PROJECT}/commit/{VERSION}#{FILE}',
            'log_url' => 'http://localhost:10080/{PROJECT}/commits/{VERSION}/{FILE}',
        ];

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
        system("curl -Ss $headers -X POST --data @{$payload} {$url}");
    }
}
