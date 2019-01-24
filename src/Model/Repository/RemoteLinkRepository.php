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
    public const DEFAULT_RELATION = 'mentioned in';

    public function addRemoteLink(int $issue_id, string $url, string $title, string $relation = self::DEFAULT_RELATION, ?string $gid = null): Entity\RemoteLink
    {
        // if gid present, lookup for existing link
        if ($gid) {
            $entity = $this->findOneBy(['issue_id' => $issue_id, 'gid' => $gid]);
        } else {
            $entity = null;
        }

        // not found, or no gid
        if (!$entity) {
            $entity = (new Entity\RemoteLink())
                ->setIssueId($issue_id)
                // empty gid means null
                ->setGid($gid ?: null);
        }

        $entity
            ->setRelation($relation)
            ->setUrl($url)
            ->setTitle($title);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        return $entity;
    }
}
