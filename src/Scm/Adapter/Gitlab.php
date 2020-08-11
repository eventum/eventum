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

namespace Eventum\Scm\Adapter;

use Eventum\Db\Doctrine;
use Eventum\Event\SystemEvents;
use Eventum\EventDispatcher\EventManager;
use Eventum\Scm\Payload\GitlabPayload;
use Eventum\Scm\ScmRepository;
use Eventum\ServiceContainer;
use Eventum\TextMatcher\GroupMatcher;
use Eventum\TextMatcher\TextMatchInterface;
use InvalidArgumentException;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\Request;

/**
 * Gitlab SCM handler
 *
 * @see https://docs.gitlab.com/ce/user/project/integrations/webhooks.html
 */
class Gitlab extends AbstractAdapter
{
    public const GITLAB_HEADER = 'X-Gitlab-Event';

    /**
     * {@inheritdoc}
     */
    public function can(): bool
    {
        // must be POST
        if ($this->request->getMethod() !== Request::METHOD_POST) {
            return false;
        }

        return $this->request->headers->has(self::GITLAB_HEADER);
    }

    /**
     * {@inheritdoc}
     */
    public function process(): void
    {
        $eventType = $this->request->headers->get(self::GITLAB_HEADER);
        $payload = $this->getPayload();

        if ($eventType === 'Push Hook') {
            $this->processPushHook($payload);
        } elseif ($eventType === 'Issue Hook' && $payload->getEventType() === GitlabPayload::EVENT_TYPE_ISSUE) {
            $this->processIssueHook($payload);
        } elseif ($eventType === 'Merge Request Hook' && $payload->getEventType() === GitlabPayload::EVENT_TYPE_MERGE_REQUEST) {
            $this->processMergeRequestHook($payload);
        } elseif ($eventType === 'Note Hook' && $payload->getEventType() === GitlabPayload::EVENT_TYPE_NOTE) {
            $this->processNoteHook($payload);
        } elseif ($eventType === 'System Hook' && $payload->getEventName() === 'push') {
            // system hook can also handle pushes
            // unfortunately it has empty commits[]
            $this->processPushHook($payload);
        }
    }

    private function processIssueHook(GitlabPayload $payload): void
    {
        if (!in_array($payload->getAction(), ['open', 'update'], true)) {
            return;
        }

        $this->matchIssues($payload);
    }

    private function processMergeRequestHook(GitlabPayload $payload): void
    {
        if (!in_array($payload->getAction(), ['open', 'update'], true)) {
            return;
        }

        $this->matchIssues($payload);
    }

    private function processNoteHook(GitlabPayload $payload): void
    {
        $this->matchIssues($payload);
    }

    private function matchIssues(GitlabPayload $payload): void
    {
        $fn = static function (TextMatchInterface $matcher, GitlabPayload $payload) {
            $groups = [
                'title' => $payload->getTitle(),
                'description' => $payload->getDescription(),
            ];

            $result = $issues = [];
            foreach ($groups as $name => $text) {
                if (!$text) {
                    continue;
                }
                $matches = iterator_to_array($matcher->match($text));
                if (!$matches) {
                    continue;
                }

                yield $name => $text;
                yield "{$name}_matches" => $matches;
                $result[$name] = $matches;

                foreach ($matches as $match) {
                    $issues[$match['issueId']] = true;
                }
            }

            // add simple structure
            yield 'matches' => $result;
            yield 'issues' => array_keys($issues);
        };

        $matcher = GroupMatcher::create();
        $data = iterator_to_array($fn($matcher, $payload));
        if (!$data || !$data['issues']) {
            return;
        }
        $data['url'] = $payload->getUrl();

        // dispatch matches as event
        $event = new GenericEvent($payload, $data);
        EventManager::dispatch(SystemEvents::RPC_GITLAB_MATCH_ISSUE, $event);
    }

    /**
     * Walk over commit messages and match issue ids
     */
    private function processPushHook(GitlabPayload $payload): void
    {
        $repo_url = $payload->getRepoUrl();
        $repo = ScmRepository::getRepoByUrl($repo_url);
        if (!$repo) {
            throw new InvalidArgumentException("SCM repo not identified from {$repo_url}");
        }

        $em = ServiceContainer::getEntityManager();
        $cr = Doctrine::getCommitRepository();
        $matcher = GroupMatcher::create();

        foreach ($payload->getCommits() as $commit) {
            $matches = iterator_to_array($matcher->match($commit['message']));
            if (!$matches) {
                continue;
            }
            $issues = array_unique(array_column($matches, 'issueId'));
            if (!$issues) {
                continue;
            }
            $branch = $payload->getBranch();
            $this->log->debug('commit', ['issues' => $issues, 'branch' => $branch, 'commit' => $commit]);

            if (!$repo->branchAllowed($branch)) {
                throw new InvalidArgumentException("Branch not allowed: {$branch}");
            }

            $ci = $payload->createCommit($commit);
            $ci->setScmName($repo->getName());
            $ci->setProjectName($payload->getProject());
            $ci->setBranch($branch);
            $cr->preCommit($ci, $payload);
            $em->persist($ci);

            // save commit files
            $cr->addCommitFiles($ci, $commit);
            // add commits to issues
            $cr->addIssues($ci, $issues);
        }

        $em->flush();
    }

    /*
     * Get Hook Payload
     */
    private function getPayload(): GitlabPayload
    {
        $data = json_decode($this->request->getContent(), true);

        return new GitlabPayload($data);
    }
}
