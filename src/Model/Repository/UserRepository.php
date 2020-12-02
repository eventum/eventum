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

use Eventum\Model\Entity;

/**
 * @method Entity\User findById(int $usr_id)
 * @method Entity\User|null findOneByCustomerContactId(int $customerContactId)
 * @method Entity\User|null findOneByExternalId(string $externalId)
 */
class UserRepository extends BaseRepository
{
    use Traits\FindByIdTrait;
}
