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

use Date_Helper;
use Eventum\Model\Entity;
use Eventum\Model\Repository\CommitRepository;

/**
 * Gitlab SCM handler
 *
 * @link http://doc.gitlab.com/ce/web_hooks/web_hooks.html
 * @package Eventum\Scm\Adapter
 */
class GitlabScm extends AbstractScmAdapter
{
    const GITLAB_HEADER = 'X-Gitlab-Event';

    /**
     * @inheritdoc
     */
    public function can()
    {
        return $this->request->headers->has(self::GITLAB_HEADER);
    }

    /**
     * @inheritdoc
     */
    public function process()
    {
        $eventType = $this->request->headers->get(self::GITLAB_HEADER);

        if ($eventType == 'Push Hook') {
            $this->processPushHook();
        }
    }

    /**
     * Walk over commit messages and match issue ids
     */
    private function processPushHook()
    {
        $payload = $this->getPayload();
        $this->log->debug('processPushHook', array('payload' => $payload->getPayload()));

        $repo_url = $payload->getRepoUrl();
        $repo = Entity\CommitRepo::getRepoByUrl($repo_url);
        if (!$repo) {
            throw new \InvalidArgumentException("SCM repo not identified from {$repo_url}");
        }

        $cr = CommitRepository::create();
        foreach ($payload->getCommits() as $commit) {
            $issues = $this->match_issues($commit['message']);
            if (!$issues) {
                continue;
            }
            $branch = $payload->getBranch();
            $this->log->debug('commit', array('issues' => $issues, 'branch' => $branch, 'commit' => $commit));

            if (!$repo->branchAllowed($branch)) {
                throw new \InvalidArgumentException("Branch not allowed: {$branch}");
            }

            $ci = Entity\Commit::create()->findOneByChangeset($commit['id']);
            if ($ci) {
                // commit already seen, skip
                continue;
            }

            $ci = $this->createCommit($commit);
            $ci->setScmName($repo->getName());
            $ci->setProjectName($payload->getProject());
            $ci->setBranch($branch);
            $cr->preCommit($ci, $payload);
            $this->addCommitFiles($ci, $commit);

            foreach ($issues as $issue_id) {
                Entity\IssueCommit::create()
                    ->setCommitId($ci->getId())
                    ->setIssueId($issue_id)
                    ->save();
                $cr->addCommit($issue_id, $ci);
            }
        }
    }

    /**
     * @param array $commit
     * @return Entity\Commit
     */
    protected function createCommit($commit)
    {
        return Entity\Commit::create()
            ->setChangeset($commit['id'])
            ->setAuthorEmail($commit['author']['email'])
            ->setAuthorName($commit['author']['name'])
            ->setCommitDate(Date_Helper::getDateTime($commit['timestamp']))
            ->setMessage(trim($commit['message']));
    }

    /**
     * Add commit files from $commit array
     *
     * @param array $commit
     */
    private function addCommitFiles(Entity\Commit $ci, $commit)
    {
        $ci->save();

        foreach ($commit['added'] as $file) {
            $cf = Entity\CommitFile::create()
                ->setCommitId($ci->getId())
                ->setFilename($file);
            $cf->save();
            $ci->addFile($cf);
        }
        foreach ($commit['modified'] as $file) {
            $cf = Entity\CommitFile::create()
                ->setCommitId($ci->getId())
                ->setFilename($file);
            $cf->save();
            $ci->addFile($cf);
        }
        foreach ($commit['removed'] as $file) {
            $cf = Entity\CommitFile::create()
                ->setCommitId($ci->getId())
                ->setFilename($file);
            $cf->save();
            $ci->addFile($cf);
        }
    }

    /*
     * Get Hook Payload
     */
    private function getPayload()
    {
        $data = json_decode($this->request->getContent(), true);

        return new Entity\GitlabPayload($data);
    }
}
