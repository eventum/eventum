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
use Eventum\Model\Entity;

/**
 * @method persistAndFlush(Entity\RemoteLink $entity)
 */
class RemoteLinkRepository extends BaseRepository
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

        // not found, or no gid
        if (!$entity) {
            $entity = new Entity\RemoteLink($issue_id, $gid);
        }

        $entity
            ->setUpdatedDate(new DateTime())
            ->setRelation($relation)
            ->setUrl($url)
            ->setTitle($title);

        $this->persistAndFlush($entity);

        return $entity;
    }
}
