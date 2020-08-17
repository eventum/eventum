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
use Eventum\Test\Traits\DataFileTrait;

class ScmControllerTest extends TestCase
{
    use DataFileTrait;

    public function testNothing(): void
    {
        $json = $this->makeRequest();
        $this->assertEquals('0', $json['code']);
        $this->assertEquals('', $json['message']);
    }

    public function testGitlab(): void
    {
        $payload = $this->readDataFile('gitlab/push/project-commit.json');

        $files = [];
        $this->addFilesListener($files);

        $json = $this->makeRequest($payload, ['HTTP_X-Gitlab-Event' => 'Push Hook']);
        $this->assertEquals('0', $json['code']);
        $this->assertEquals("#1 - Issue Summary #1 (discovery)\n", $json['message']);

        $this->assertEquals(['bla'], $files);
    }

    /**
     * @dataProvider GitlabCommitNoteData
     */
    public function testGitlabCommitNote(string $input): void
    {
        $payload = $this->readDataFile($input);

        $invoked = false;
        $listener = static function () use (&$invoked): void {
            $invoked = true;
        };

        $dispatcher = EventManager::getEventDispatcher();
        $dispatcher->addListener(SystemEvents::RPC_GITLAB_MATCH_ISSUE, $listener);

        $json = $this->makeRequest($payload, ['HTTP_X-Gitlab-Event' => 'Note Hook']);
        $expected = ['code' => 0, 'message' => ''];
        $this->assertEquals($expected, $json);
        $this->assertFalse($invoked);
    }

    public function GitlabCommitNoteData(): iterable
    {
        yield ['gitlab/note/commit-note.json'];
        yield ['gitlab/note/commit-diff-note.json'];
    }

    public function testCvs(): void
    {
        $payload = $this->readDataFile('cvs-commit.json');

        $files = [];
        $this->addFilesListener($files);

        $json = $this->makeRequest($payload, [], ['scm' => 'cvs']);
        $this->assertEquals('0', $json['code']);
        $this->assertEquals("#1 - Issue Summary #1 (discovery)\n", $json['message']);

        $this->assertEquals(['test/a/test'], $files);
    }

    private function makeRequest(string $content = null, array $headers = [], $query = []): array
    {
        $client = static::createClient();

        $url = '/scm_ping.php';
        if ($query) {
            $url .= '?' . http_build_query($query);
        }

        $client->request('POST', $url, [], [], $headers, $content);
        $response = $client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());

        return json_decode($response->getContent(), 1);
    }
}
