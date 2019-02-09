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

use DateTime;
use Doctrine\ORM\EntityRepository;
use Eventum\Model\Entity;

class RemoteLinkRepository extends EntityRepository
{
    public const DEFAULT_RELATION = 'mentioned in';

    /**
     * @param int $issue_id
     * @return Entity\RemoteLink[]
     */
    public function getRemoteLinks(int $issue_id): array
    {
        return $this->findBy(['issue_id' => $issue_id]);
    }

    public function addRemoteLink(int $issue_id, string $url, string $title, ?string $gid = null, string $relation = self::DEFAULT_RELATION): Entity\RemoteLink
    {
        // if gid present, lookup for existing link
        if ($gid) {
            $entity = $this->findOneBy(['issue_id' => $issue_id, 'gid' => $gid]);
        } else {
            $entity = null;
        }

        $now = new DateTime();

        // not found, or no gid
        if (!$entity) {
            $entity = (new Entity\RemoteLink())
                ->setCreatedDate($now)
                ->setIssueId($issue_id)
                // empty gid means null
                ->setGid($gid ?: null);
        }

        $entity
            ->setUpdatedDate($now)
            ->setRelation($relation)
            ->setUrl($url)
            ->setTitle($title);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        return $entity;
    }
}
