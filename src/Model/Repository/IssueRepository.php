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
use Eventum\Model\Entity;

class IssueRepository extends EntityRepository
{
    use Traits\FindByIdTrait;

    /**
     * @param int $issue_id
     * @return Entity\Commit[]
     */
    public function getCommits($issue_id)
    {
        $issue = $this->findOneBy(['id' => $issue_id]);
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
