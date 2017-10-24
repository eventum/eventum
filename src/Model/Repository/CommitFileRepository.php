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

class CommitFileRepository extends EntityRepository
{
    /**
     * @param int $cid
     * @return Entity\CommitFile[]
     */
    public function findByCommitId($cid)
    {
        return $this->findBy(['cof_com_id' => $cid]);
    }
}
