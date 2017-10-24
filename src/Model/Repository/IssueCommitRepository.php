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
use Issue;

class IssueCommitRepository extends EntityRepository
{
    /**
     * @param int $issue_id
     * @return Entity\IssueCommit[]
     */
    public function findByIssueId($issue_id)
    {
        return $this->findBy(['isc_iss_id' => $issue_id]);
    }

    /**
     * @param int $issue_id
     * @internal used for tests
     */
    public function deleteAllRelations($issue_id)
    {
        $this
            ->createQueryBuilder('q')
            ->delete(Entity\IssueCommit::class, 'c')
            ->where('c.isc_iss_id=:issue_id')
            ->setParameter('issue_id', $issue_id)
            ->getQuery()
            ->execute();
    }
}
