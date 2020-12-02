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
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Eventum\Db\DatabaseException;

abstract class BaseRepository extends EntityRepository
{
    public function persistAndFlush($entity): void
    {
        $em = $this->getEntityManager();
        try {
            $em->persist($entity);
            $em->flush();
        } catch (ORMException | OptimisticLockException $e) {
            throw new DatabaseException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
