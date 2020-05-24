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

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\EntityRepository;
use Eventum\Model\Entity;

/**
 * @method Entity\EmailAccount findById(int $iss_id)
 */
class EmailAccountRepository extends EntityRepository
{
    use Traits\FindByIdTrait;

    public function findByDSN(string $hostname, string $username, ?string $folder): Entity\EmailAccount
    {
        $criteria = [
            'username' => $username,
            'hostname' => $hostname,
        ];
        if ($folder) {
            $criteria['folder'] = $folder;
        }

        /** @var Entity\EmailAccount $res */
        $res = $this->findOneBy($criteria);
        if (!$res) {
            $type = get_class($this);
            throw EntityNotFoundException::fromClassNameAndIdentifier($type, $criteria);
        }

        return $res;
    }

    public function persistAndFlush(Entity\EmailAccount $account): void
    {
        $em = $this->getEntityManager();
        $em->persist($account);
        $em->flush($account);
    }
}
