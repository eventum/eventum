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

namespace Eventum\Model\Repository\Traits;

use Doctrine\ORM\EntityNotFoundException;

trait FindByIdTrait
{
    /**
     * @param int $id the identifier
     * @throws EntityNotFoundException
     * @return mixed
     */
    public function findById($id)
    {
        $res = $this->find($id);
        if (!$res) {
            throw new EntityNotFoundException();
        }

        return $res;
    }
}
