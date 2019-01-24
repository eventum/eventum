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

class RemoteLinkRepository extends EntityRepository
{
    public function addRemoteLink(string $url, string $title, string $relationship = 'links to', ?string $gid = null): Entity\RemoteLink
    {
        $entity = new Entity\RemoteLink();
        $entity
            ->setGid($gid)
            ->setRelationship($relationship)
            ->setUrl($url)
            ->setTitle($title);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        return $entity;
    }
}
