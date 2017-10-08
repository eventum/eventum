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

use Eventum\Monolog\Logger;
use Eventum\Scm\Adapter\GitlabScm;
use Setup;
use Symfony\Component\HttpFoundation\Request;

class ScmApiCommitTest extends ScmTestCase
{
    /**
     * Test commit push over Api
     */
    public function testGitlabCommitApi()
    {
        $api_url = $this->getCommitUrl();
        $payload = $this->readDataFile('gitlab-commit.json');

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

        $payload = $this->getDataFile('gitlab-commit.json');
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
