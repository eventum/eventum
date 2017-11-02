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

use Doctrine\ORM\EntityRepository;
use Eventum\Db\Doctrine;
use Eventum\Model\Entity;
use Eventum\Scm\Payload;
use History;
use Issue;
use Workflow;

class CommitRepository extends EntityRepository
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
     * Method called on Commit to allow workflow update project name/commit author or user id
     *
     * @param int $prj_id The project ID
     * @param Entity\Commit $ci
     * @param Payload\GitlabPayload|Payload\StandardPayload $payload
     */
    public function preCommit($prj_id, Entity\Commit $ci, $payload)
    {
        Workflow::preScmCommit($prj_id, $ci, $payload);
    }

    /**
     * Associate commit to an existing issue,
     * additionally notifies workflow about new commit
     *
     * @param int $issue_id the ID of the issue
     * @param Entity\Commit $commit
     */
    public function addCommit($issue_id, Entity\Commit $commit)
    {
        $prj_id = Issue::getProjectID($issue_id);

        Issue::markAsUpdated($issue_id, 'scm checkin');

        // TODO: add workflow pre method first, so it may setup username, etc
        $usr_id = APP_SYSTEM_USER_ID;

        // need to save a history entry for this
        // TRANSLATORS: %1: scm username
        History::add(
            $issue_id, $usr_id, 'scm_checkin_associated', "SCM Checkins associated by SCM user '{user}'", [
                'user' => $commit->getAuthor(),
            ]
        );

        // notify workflow about new commit
        Workflow::handleScmCommit($prj_id, $issue_id, $commit);
    }

    /**
     * Add commit files from $commit array
     *
     * @param Entity\Commit $ci
     * @param array $commit
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function addCommitFiles(Entity\Commit $ci, $commit)
    {
        $em = Doctrine::getEntityManager();

        foreach ($commit['added'] as $filename) {
            $cf = (new Entity\CommitFile())
                ->setCommit($ci->getId())
                ->setAdded(true)
                ->setFilename($filename);

            if (isset($commit['versions'][$filename])) {
                $this->setFileVersions($cf, $commit['versions'][$filename]);
            }

            $em->persist($cf);
            $em->flush();
            $ci->addFile($cf);
        }

        foreach ($commit['modified'] as $filename) {
            $cf = (new Entity\CommitFile())
                ->setCommit($ci->getId())
                ->setModified(true)
                ->setFilename($filename);

            if (isset($commit['versions'][$filename])) {
                $this->setFileVersions($cf, $commit['versions'][$filename]);
            }

            $em->persist($cf);
            $em->flush();
            $ci->addFile($cf);
        }

        foreach ($commit['removed'] as $filename) {
            $cf = (new Entity\CommitFile())
                ->setCommit($ci->getId())
                ->setRemoved(true)
                ->setFilename($filename);

            if (isset($commit['versions'][$filename])) {
                $this->setFileVersions($cf, $commit['versions'][$filename]);
            }

            $em->persist($cf);
            $em->flush();
            $ci->addFile($cf);
        }
    }

    /**
     * @param Entity\CommitFile $cf
     * @param array $versions
     */
    private function setFileVersions(Entity\CommitFile $cf, $versions)
    {
        if (isset($versions[0])) {
            $cf->setOldVersion($versions[0]);
        }
        if (isset($versions[1])) {
            $cf->setNewVersion($versions[1]);
        }
    }

    /**
     * @param int $issue_id
     * @return Entity\Commit[]
     */
    public function getIssueCommits($issue_id)
    {
        $repo = Doctrine::getIssueRepository();

        /** @var Entity\Issue $issue */
        $issue = $repo->findOneBy(['id' => $issue_id]);
        if (!$issue) {
            return [];
        }

        $commits = $issue->getCommits();
        if (!count($commits)) {
            return [];
        }

        $res = iterator_to_array($commits);

        // order by date
        // need userspace sort as the sort column is in commit table
        // but we select from issue_commit table
        $sorter = function (Entity\Commit $ca, Entity\Commit $cb) {
            $a = $ca->getCommitDate()->getTimestamp();
            $b = $cb->getCommitDate()->getTimestamp();

            return ($a < $b) ? -1 : (($a > $b) ? 1 : 0);
        };

        uasort($res, $sorter);

        return $res;
    }
}
