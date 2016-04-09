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
     * Associate commit to an existing issue,
     * additionally notifies workflow about new commit
     *
     * @param int $issue_id The ID of the issue.
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
            $issue_id, $usr_id, 'scm_checkin_associated', "SCM Checkins associated by SCM user '{user}'", array(
                'user' => $commit->getAuthor(),
            )
        );

        // notify workflow about new commit
        Workflow::handleScmCommit($prj_id, $issue_id, $commit);
    }

    /**
     * @param int $issue_id
     * @return Entity\Commit[]
     */
    public function getIssueCommits($issue_id)
    {
        $res = array();

        $ics = Entity\IssueCommit::create()->findByIssueId($issue_id);
        if (!$ics) {
            return array();
        }

        // associate commits
        foreach ($ics as $ic) {
            $cs = Entity\Commit::create()->findById($ic->getCommitId());
            foreach ($cs as $c) {
                // associate files
                $cfs = Entity\CommitFile::create()->findByCommitId($c->getId());
                foreach ($cfs as $cf) {
                    $c->addFile($cf);
                }
                $res[] = $c;
            }
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
     * @param   integer $issue_id The issue ID
     * @return  array The list of checkins
     */
    public function getIssueCommitsArray($issue_id)
    {
        $res = $this->getIssueCommits($issue_id);

        $checkins = array();
        foreach ($res as $c) {
            $scm = $c->getCommitRepo();

            $checkin = $c->toArray();
            $checkin['isc_commit_date'] = Date_Helper::convertDateGMT($checkin['com_commit_date']);
            $checkin['isc_commit_msg'] = Link_Filter::processText(
                Issue::getProjectID($issue_id), nl2br(htmlspecialchars($checkin['com_message']))
            );
            $checkin['author'] = $c->getAuthor();
            $checkin['files'] = array();
            foreach ($c->getFiles() as $cf) {
                $f = $cf->toArray();

                // add ADDED and REMOVED fields
                $f['added'] = !isset($f['cof_old_version']);
                $f['removed'] = !isset($f['cof_new_version']);

                // fill urls
                $f['checkout_url'] = $scm->getCheckoutUrl($f);
                $f['diff_url'] = $scm->getDiffUrl($f);
                $f['scm_log_url'] = $scm->getLogUrl($f);

                $checkin['files'][] = $f;
            }
            $checkins[$c->getCommitId()] = $checkin;
        }

        return $checkins;
    }
}
