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

use Eventum\Event\SystemEvents;
use Eventum\EventDispatcher\EventManager;
use Eventum\Model\Entity;
use Eventum\Monolog\Logger;
use Eventum\Scm\Adapter\Cvs;
use Eventum\Scm\Adapter\Gitlab;
use Setup;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\Request;

/**
 * @group api
 */
class ScmApiCommitTest extends ScmTestCase
{
    /**
     * Test commit push over Api
     */
    public function testGitlabCommitApi()
    {
        $request = $this->createApiRequest('gitlab-commit.json');
        $request->headers->set(Gitlab::GITLAB_HEADER, 'Push Hook');

        $logger = Logger::app();
        $handler = new Gitlab($request, $logger);
        $this->assertTrue($handler->can());

        $files = [];
        $this->addFilesListener($files);
        $handler->process();

        $this->assertEquals(['bla'], $files);
    }

    public function testCvsCommitApi()
    {
        $this->markTestIncomplete('lacks cleanup, so will fail on second run');
        $request = $this->createApiRequest('cvs-commit.json');
        $request->query->set('scm', 'cvs');

        $logger = Logger::app();
        $handler = new Cvs($request, $logger);
        $this->assertTrue($handler->can());

        $files = [];
        $this->addFilesListener($files);
        $handler->process();

        $this->assertEquals(['test/a/test'], $files);
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

    private function createApiRequest($filename)
    {
        $api_url = $this->getCommitUrl();
        $payload = $this->readDataFile($filename);

        return Request::create($api_url, 'POST', [], [], [], [], $payload);
    }

    private function addFilesListener(&$files)
    {
        $listener = function (GenericEvent $event) use (&$files) {
            /** @var Entity\Commit $commit */
            $commit = $event->getSubject();
            foreach ($commit->getFiles() as $cf) {
                $files[] = $cf->getFilename();
            }
        };

        $dispatcher = EventManager::getEventDispatcher();
        $dispatcher->addListener(SystemEvents::SCM_COMMIT_ASSOCIATED, $listener);
    }
}
