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

/**
 * @method Entity\Issue findById(int $iss_id)
 */
class IssueRepository extends BaseRepository
{
    use Traits\FindByIdTrait;

    /**
     * @param int $issue_id
     * @return Entity\Commit[]
     */
    public function getCommits(int $issue_id): array
    {
        $issue = $this->findById($issue_id);
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
