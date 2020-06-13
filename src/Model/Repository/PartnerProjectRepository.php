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
use Eventum\Db\DatabaseException;
use Eventum\Model\Entity;
use Partner;

/**
 * @method Entity\PartnerProject findById(int $prj_id)
 * @method Entity\PartnerProject findOneByCode(string $code)
 */
class PartnerProjectRepository extends EntityRepository
{
    use Traits\FindByIdTrait;

    public function setProjectAssociation(Entity\PartnerProject $pap, array $projects): void
    {
        $res = Partner::update($pap->getCode(), $projects);

        if ($res === -1) {
            throw new DatabaseException();
        }
    }
}
