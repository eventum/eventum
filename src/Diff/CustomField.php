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

namespace Eventum\Diff;

use Eventum\Model\Entity;

class CustomField
{
    public function diff(array $updated_fields, ?int $role_id = null): array
    {
        if ($role_id) {
            $updated_fields = $this->getUpdatedFieldsForRole($updated_fields, $role_id);
        }

        $builder = new Builder();
        foreach ($updated_fields as $fld_id => $field) {
            if ($field['old_display'] !== $field['new_display']) {
                if ($field['type'] === Entity\CustomField::TYPE_TEXTAREA) {
                    $builder->addTextChange($field['title'], $field['old_display'], $field['new_display']);
                } else {
                    $builder->addChange($field['title'], $field['old_display'], $field['new_display']);
                }
            }
        }

        return $builder->getChanges();
    }

    /**
     * Returns custom field updates that are visible to the specified role
     *
     * @param   array $updated_fields
     * @param   int $role_id
     * @return  array
     */
    private function getUpdatedFieldsForRole(array $updated_fields, ?int $role_id = null): array
    {
        $role_updates = [];
        foreach ($updated_fields as $fld_id => $field) {
            if ($role_id >= $field['min_role']) {
                $role_updates[$fld_id] = $field;
            }
        }

        return $role_updates;
    }
}
