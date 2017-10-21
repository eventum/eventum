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

use Eventum\Model\Entity;
use Eventum\Model\Repository\CommitRepository;
use Eventum\Scm\Payload\GitlabPayload;
use Eventum\Scm\ScmRepository;
use InvalidArgumentException;
use Issue;
use Symfony\Component\HttpFoundation\Request;

/**
 * Gitlab SCM handler
 *
 * @see http://doc.gitlab.com/ce/web_hooks/web_hooks.html
 */
class Gitlab extends AbstractAdapter
{
    const GITLAB_HEADER = 'X-Gitlab-Event';

    /**
     * {@inheritdoc}
     */
    public function can()
    {
        // must be POST
        if ($this->request->getMethod() != Request::METHOD_POST) {
            return false;
        }

        return $this->request->headers->has(self::GITLAB_HEADER);
    }

    /**
     * {@inheritdoc}
     */
    public function process()
    {
        $eventType = $this->request->headers->get(self::GITLAB_HEADER);
        $payload = $this->getPayload();

        if ($eventType === 'Push Hook') {
            $this->processPushHook($payload);
        } elseif ($eventType === 'System Hook' && $payload->getEventName() === 'push') {
            // system hook can also handle pushes
            // unfortunately it has empty commits[]
            $this->processPushHook($payload);
        }
    }

    /**
     * Walk over commit messages and match issue ids
     *
     * @param GitlabPayload $payload
     * @throws InvalidArgumentException
     */
    private function processPushHook(GitlabPayload $payload)
    {
        $repo_url = $payload->getRepoUrl();
        $repo = ScmRepository::getRepoByUrl($repo_url);
        if (!$repo) {
            throw new InvalidArgumentException("SCM repo not identified from {$repo_url}");
        }

        $cr = CommitRepository::create();
        foreach ($payload->getCommits() as $commit) {
            $issues = $this->match_issues($commit['message']);
            if (!$issues) {
                continue;
            }
            $branch = $payload->getBranch();
            $this->log->debug('commit', ['issues' => $issues, 'branch' => $branch, 'commit' => $commit]);

            if (!$repo->branchAllowed($branch)) {
                throw new InvalidArgumentException("Branch not allowed: {$branch}");
            }

            // XXX: take prj_id from first issue_id
            $prj_id = Issue::getProjectID($issues[0]);

            $ci = $payload->createCommit($commit);
            $ci->setScmName($repo->getName());
            $ci->setProjectName($payload->getProject());
            $ci->setBranch($branch);
            $cr->preCommit($prj_id, $ci, $payload);
            $ci->save();

            // save commit files
            $cr->addCommitFiles($ci, $commit);

            // add issue relations
            foreach ($issues as $issue_id) {
                Entity\IssueCommit::create()
                    ->setCommitId($ci->getId())
                    ->setIssueId($issue_id)
                    ->save();
                $cr->addCommit($issue_id, $ci);
            }
        }
    }

    /*
     * Get Hook Payload
     */
    private function getPayload()
    {
        $data = json_decode($this->request->getContent(), true);

        return new GitlabPayload($data);
    }
}
