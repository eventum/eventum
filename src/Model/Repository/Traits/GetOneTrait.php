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

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\ORM\PersistentCollection;
use RuntimeException;

trait GetOneTrait
{
    private function getOne(?PersistentCollection $collection, string $field, string $operator, $value)
    {
        if (!$collection) {
            return null;
        }

        $expr = new Comparison($field, $operator, $value);
        $criteria = Criteria::create()->where($expr);

        $matches = $collection->matching($criteria);
        if ($matches->isEmpty()) {
            return null;
        }

        if ($matches->count() !== 1) {
            $count = $matches->count();
            throw new RuntimeException("Expected one element, got $count");
        }

        return $matches->first() ?: null;
    }
}
