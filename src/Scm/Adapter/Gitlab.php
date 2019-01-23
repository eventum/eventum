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
use Eventum\IssueMatcher;
use Eventum\Scm\Payload\GitlabPayload;
use Eventum\Scm\ScmRepository;
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
        } elseif ($eventType === 'Issue Hook' && $payload->getEventType() === 'issue') {
            $this->processIssueHook($payload);
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

        $matcher = new IssueMatcher(APP_BASE_URL);
        $description = $payload->getDescription();
        $matches = $matcher->match($description);
        if (!$matches) {
            return;
        }

        // dispatch matches as event
        $dispatcher = EventManager::getEventDispatcher();
        $event = new GenericEvent($payload, [
            'description' => $description,
            'description_matches' => $matches,
        ]);
        $dispatcher->dispatch(SystemEvents::RPC_GITLAB_MATCH_ISSUE, $event);
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

        $em = Doctrine::getEntityManager();
        $cr = Doctrine::getCommitRepository();
        $ir = Doctrine::getIssueRepository();

        foreach ($payload->getCommits() as $commit) {
            $issues = $this->matchIssueIds($commit['message']);
            if (!$issues) {
                continue;
            }
            $branch = $payload->getBranch();
            $this->log->debug('commit', ['issues' => $issues, 'branch' => $branch, 'commit' => $commit]);

            if (!$repo->branchAllowed($branch)) {
                throw new InvalidArgumentException("Branch not allowed: {$branch}");
            }

            // XXX: take prj_id from first issue_id
            $issue = $ir->findById($issues[0]);
            $prj_id = $issue->getProjectId();

            $ci = $payload->createCommit($commit);
            $ci->setScmName($repo->getName());
            $ci->setProjectName($payload->getProject());
            $ci->setBranch($branch);
            $cr->preCommit($prj_id, $ci, $payload);
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
