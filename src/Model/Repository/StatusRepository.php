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
use Eventum\Model\Entity;

/**
 * @method Entity\Status findById(int $sta_id)
 */
class StatusRepository extends BaseRepository
{
    use Traits\FindByIdTrait;

    public function findByTitle(string $title): Entity\Status
    {
        /** @var Entity\Status $status */
        $status = $this->findOneBy(['title' => $title]);
        if (!$status) {
            $type = get_class($this);

            throw new EntityNotFoundException("$type '$title' not found");
        }

        return $status;
    }
}
