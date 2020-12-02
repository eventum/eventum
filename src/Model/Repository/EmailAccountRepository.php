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
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Eventum\Db\DatabaseException;
use Eventum\Model\Entity;

/**
 * @method Entity\EmailAccount findById(int $iss_id)
 * @method persistAndFlush(Entity\EmailAccount $entity)
 */
class EmailAccountRepository extends BaseRepository
{
    use Traits\FindByIdTrait;

    public function findOrCreate(int $id): Entity\EmailAccount
    {
        $account = $this->find($id);
        if (!$account) {
            $account = new Entity\EmailAccount();
        }

        return $account;
    }

    /**
     * @return array|Entity\EmailAccount[]
     */
    public function findAll(array $orderBy = ['hostname' => 'ASC']): array
    {
        return $this->findBy([], $orderBy);
    }

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

    public function updateAccount(Entity\EmailAccount $account): void
    {
        try {
            $this->persistAndFlush($account);
        } catch (ORMException | OptimisticLockException $e) {
            throw new DatabaseException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function removeAccount(Entity\EmailAccount $account): void
    {
        $em = $this->getEntityManager();
        $em->remove($account);
        $em->flush();
    }
}
