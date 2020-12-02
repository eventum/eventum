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
use LogicException;

/**
 * @method Entity\IssueHistory findById(int $prj_id)
 */
class IssueHistoryRepository extends BaseRepository
{
    use Traits\FindByIdTrait;

    /**
     * Returns the last person to close the issue
     *
     * @param int $issue_id The ID of the issue
     * @return int usr_id
     */
    public function getIssueCloser(int $issue_id): ?int
    {
        $typeId = $this->getTypeId('issue_closed');
        $htt = $this->findOneBy(['issueId' => $issue_id, 'typeId' => $typeId], ['createdDate' => 'DESC']);
        if (!$htt) {
            return null;
        }

        return $htt->getUserId();
    }

    private function getTypeId(string $name): int
    {
        $repo = $this->getEntityManager()->getRepository(Entity\HistoryType::class);

        $htt = $repo->findOneBy(['name' => $name]);
        if (!$htt) {
            throw new LogicException("Cannot find history type '{$name}'");
        }

        return $htt->getId();
    }
}
