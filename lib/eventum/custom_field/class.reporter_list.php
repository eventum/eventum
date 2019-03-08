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

use Eventum\CustomField\Fields\ListInterface;

/**
 * Custom field backend to return list of users with a role of reporter or above.
 */
class Reporter_List_Custom_Field_Backend implements ListInterface
{
    public function getList(int $fld_id, ?int $issue_id = null, ?string $form_type = null): array
    {
        return User::getActiveAssocList(Auth::getCurrentProject(), User::ROLE_VIEWER);
    }
}
