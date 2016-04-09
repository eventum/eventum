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

use Eventum\Model\Entity;

class CommitRepository
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
            $a = $ca->getCommitDate();
            $b = $cb->getCommitDate();
            return ($a < $b) ? -1 : (($a > $b) ? 1 : 0);
        };

        uasort($res, $sorter);

        return $res;
    }
}
