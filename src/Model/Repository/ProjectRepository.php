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
use Eventum\Model\Entity;

/**
 * @method Entity\Project findById(int $prj_id)
 */
class ProjectRepository extends EntityRepository
{
    use Traits\FindByIdTrait;

    public function findOrCreate(int $id): Entity\Project
    {
        $project = $this->find($id);
        if (!$project) {
            $project = new Entity\Project();
        }

        return $project;
    }

    public function updateProject(Entity\Project $project): void
    {
        $em = $this->getEntityManager();
        try {
            $em->persist($project);
            $em->flush();
        } catch (ORMException | OptimisticLockException $e) {
            throw new DatabaseException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
