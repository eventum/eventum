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

namespace Eventum\Model\Repository;

use Eventum\Db\Doctrine;
use Eventum\Event\SystemEvents;
use Eventum\EventDispatcher\EventManager;
use Eventum\Model\Entity;
use Eventum\Scm\Payload;
use History;
use InvalidArgumentException;
use Issue;
use Setup;
use Symfony\Component\EventDispatcher\GenericEvent;
use Workflow;

class CommitRepository extends BaseRepository
{
    /**
     * @param int $id
     * @return null|object|Entity\Commit
     */
    public function findById($id)
    {
        return $this->findOneBy(['com_id' => $id]);
    }

    /**
     * @param string $changeset
     * @return null|object|Entity\Commit
     */
    public function findOneByChangeset($changeset)
    {
        return $this->findOneBy(['com_changeset' => $changeset]);
    }

    /**
     * Method called on before storing Commit,
     * to allow workflow update project name/commit author or user id.
     *
     * @param Entity\Commit $ci
     * @param Payload\PayloadInterface $payload
     * @since 3.4.0 dispatches SystemEvents::SCM_COMMIT_BEFORE event
     */
    public function preCommit(Entity\Commit $ci, Payload\PayloadInterface $payload): void
    {
        $event = new GenericEvent($ci, ['payload' => $payload]);
        EventManager::dispatch(SystemEvents::SCM_COMMIT_BEFORE, $event);
    }

    /**
     * @param int $issue_id the ID of the issue
     * @param Entity\Commit $commit
     */
    public function notifyNewCommit($issue_id, Entity\Commit $commit): void
    {
        Issue::markAsUpdated($issue_id, 'scm checkin');

        $usr_id = $commit->getUserId() ?: Setup::getSystemUserId();

        // need to save a history entry for this
        // TRANSLATORS: %1: scm username
        History::add(
            $issue_id,
            $usr_id,
            'scm_checkin_associated',
            "SCM Checkins associated by SCM user '{user}'",
            [
                'user' => $commit->getAuthor(),
            ]
        );
    }

    /**
     * Add commit files from $commit array
     *
     * @param Entity\Commit $ci
     * @param array $commit
     */
    public function addCommitFiles(Entity\Commit $ci, $commit): void
    {
        $em = $this->getEntityManager();

        foreach ($commit['added'] as $filename) {
            $cf = (new Entity\CommitFile())
                ->setCommit($ci)
                ->setAdded(true)
                ->setFilename($filename);

            if (isset($commit['versions'][$filename])) {
                $this->setFileVersions($cf, $commit['versions'][$filename]);
            }

            $em->persist($cf);
            $ci->addFile($cf);
        }

        foreach ($commit['modified'] as $filename) {
            $cf = (new Entity\CommitFile())
                ->setCommit($ci)
                ->setModified(true)
                ->setFilename($filename);

            if (isset($commit['versions'][$filename])) {
                $this->setFileVersions($cf, $commit['versions'][$filename]);
            }

            $em->persist($cf);
            $ci->addFile($cf);
        }

        foreach ($commit['removed'] as $filename) {
            $cf = (new Entity\CommitFile())
                ->setCommit($ci)
                ->setRemoved(true)
                ->setFilename($filename);

            if (isset($commit['versions'][$filename])) {
                $this->setFileVersions($cf, $commit['versions'][$filename]);
            }

            $em->persist($cf);
            $ci->addFile($cf);
        }
    }

    /**
     * Add commit to issues. Associate commit to (several) issues.
     *
     * @param Entity\Commit $ci
     * @param int[] $issues
     * @since 3.3.4 dispatches SystemEvents::SCM_COMMIT_ASSOCIATED event
     */
    public function addIssues(Entity\Commit $ci, $issues): void
    {
        $em = $this->getEntityManager();
        $ir = Doctrine::getIssueRepository();

        // add issue association
        foreach ($issues as $issue_id) {
            /** @var Entity\Issue $issue */
            $issue = $ir->findOneBy(['id' => $issue_id]);
            if (!$issue) {
                throw new InvalidArgumentException("Invalid issue: $issue_id");
            }
            $issue->addCommit($ci);
            $em->persist($issue);

            $this->notifyNewCommit($issue_id, $ci);

            $arguments = [
                'issue_id' => $issue->getId(),
                'prj_id' => $issue->getProjectId(),
            ];
            $event = new GenericEvent($ci, $arguments);

            EventManager::dispatch(SystemEvents::SCM_COMMIT_ASSOCIATED, $event);

            // print report to stdout of commits so hook could report status back to committer
            echo "#$issue_id - {$issue->getSummary()} ({$issue->getStatusTitle()})\n";
        }
    }

    /**
     * @param Entity\CommitFile $cf
     * @param array $versions
     */
    private function setFileVersions(Entity\CommitFile $cf, $versions): void
    {
        if (isset($versions[0])) {
            $cf->setOldVersion($versions[0]);
        }
        if (isset($versions[1])) {
            $cf->setNewVersion($versions[1]);
        }
    }
}
