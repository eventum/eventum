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
use Eventum\Model\Entity\UserPreference;
use Eventum\Model\Repository\Traits\FindByIdTrait;

/**
 * @method UserPreference findById(int $usr_id)
 */
class UserPreferenceRepository extends EntityRepository
{
    use FindByIdTrait;

    public function persistAndFlush(UserPreference $entity): void
    {
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush($entity);
    }

    public function findOrCreate(int $id): UserPreference
    {
        $cf = $this->find($id);
        if (!$cf) {
            $cf = new UserPreference();
        }

        return $cf;
    }
}
