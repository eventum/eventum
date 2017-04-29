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

use Date_Helper;
use Eventum\Model\Entity;
use History;
use Issue;
use Link_Filter;
use Workflow;

class CommitRepository extends BaseRepository
{
    /**
     * Method called on Commit to allow workflow update project name/commit author or user id
     *
     * @param int $prj_id The project ID.     *
     * @param Entity\Commit $ci
     * @param Entity\GitlabScmPayload|Entity\StdScmPayload $payload
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
     * @param array $commit
     */
    public function addCommitFiles(Entity\Commit $ci, $commit)
    {
        foreach ($commit['added'] as $filename) {
            $cf = Entity\CommitFile::create()
                ->setCommitId($ci->getId())
                ->setAdded(true)
                ->setFilename($filename);

            if (isset($commit['versions'][$filename])) {
                $this->setFileVersions($cf, $commit['versions'][$filename]);
            }

            $cf->save();
            $ci->addFile($cf);
        }

        foreach ($commit['modified'] as $filename) {
            $cf = Entity\CommitFile::create()
                ->setCommitId($ci->getId())
                ->setModified(true)
                ->setFilename($filename);

            if (isset($commit['versions'][$filename])) {
                $this->setFileVersions($cf, $commit['versions'][$filename]);
            }

            $cf->save();
            $ci->addFile($cf);
        }

        foreach ($commit['removed'] as $filename) {
            $cf = Entity\CommitFile::create()
                ->setCommitId($ci->getId())
                ->setRemoved(true)
                ->setFilename($filename);

            if (isset($commit['versions'][$filename])) {
                $this->setFileVersions($cf, $commit['versions'][$filename]);
            }

            $cf->save();
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
        $res = [];

        $ics = Entity\IssueCommit::create()->findByIssueId($issue_id);
        if (!$ics) {
            return [];
        }

        // associate commits
        foreach ($ics as $ic) {
            $c = Entity\Commit::create()->findById($ic->getCommitId());
            // associate files
            $files = Entity\CommitFile::create()->findByCommitId($c->getId()) ?: [];
            foreach ($files as $cf) {
                $c->addFile($cf);
            }
            $res[] = $c;
        }

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

    /**
     * Get commits related to issue formatted to array for templating
     *
     * @param   int $issue_id The issue ID
     * @return  array The list of checkins
     */
    public function getIssueCommitsArray($issue_id)
    {
        $res = $this->getIssueCommits($issue_id);

        $checkins = [];
        foreach ($res as $c) {
            $scm = $c->getCommitRepo();

            $checkin = $c->toArray();
            $checkin['isc_commit_date'] = Date_Helper::convertDateGMT($checkin['com_commit_date']);
            $checkin['isc_commit_msg'] = Link_Filter::processText(
                Issue::getProjectID($issue_id), nl2br(htmlspecialchars($checkin['com_message']))
            );
            $checkin['author'] = $c->getAuthor();
            $checkin['project_name'] = $c->getProjectName();
            $checkin['branch'] = $c->getBranch();
            $checkin['commit_short'] = $c->getChangeset(true);
            $checkin['changeset_url'] = $scm->getChangesetUrl($c);
            $checkin['branch_url'] = $scm->getBranchUrl($c);
            $checkin['project_url'] = $scm->getProjectUrl($c);
            $checkin['files'] = [];
            foreach ($c->getFiles() as $cf) {
                $f = $cf->toArray();

                $f['added'] = $cf->isAdded();
                $f['removed'] = $cf->isRemoved();
                $f['modified'] = $cf->isModified();

                // flag indicating whether file has versions
                $f['versions'] = $cf->hasVersions();

                // fill for url builder
                $f['project_name'] = $c->getProjectName();

                // fill urls
                $f['checkout_url'] = $scm->getCheckoutUrl($c, $cf);
                $f['diff_url'] = $scm->getDiffUrl($c, $cf);
                $f['scm_log_url'] = $scm->getLogUrl($c, $cf);

                $checkin['files'][] = $f;
            }
            $checkins[] = $checkin;
        }

        return $checkins;
    }
}
