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

/**
 * Class UserRepository
 *
 * @method Entity\User|null findOneByCustomerContactId(int $customerContactId)
 */
class UserRepository extends EntityRepository
{
    /**
     * Method used to get the user ID associated with the given customer
     * contact ID.
     *
     * @param int $customerContactId The customer contact ID
     * @return Entity\User|null
     */
    public function findByContactId($customerContactId)
    {
        /** @var UserRepository $repo */
        $repo = $this->getEntityManager()->getRepository(Entity\User::class);

        return $repo->findOneByCustomerContactId($customerContactId);
    }
}
