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
use Issue;
use Link_Filter;

class CommitRepository extends BaseRepository
{
    /**
     * @param int $issue_id
     * @return Entity\Commit[]
     */
    public function getIssueCommits($issue_id)
    {
        $res = array();

        $ics = Entity\IssueCommit::create()->findByIssueId($issue_id);

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
