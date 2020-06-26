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

namespace Eventum\Markdown\CommonMark;

use Eventum\Db\Doctrine;
use Eventum\Model\Entity\User;

class UserLookup
{
    public function findUser(?string $handle): ?User
    {
        if (!$handle) {
            return null;
        }
        $repo = Doctrine::getUserRepository();

        return $repo->findOneByExternalId($handle);
    }
}
