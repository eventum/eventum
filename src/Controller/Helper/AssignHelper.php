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

namespace Eventum\Controller\Helper;

use Auth;
use Group;
use User;

class AssignHelper
{
    /** @var int */
    private $usr_id;

    /** @var int */
    private $prj_id;

    /**
     * Generate options for assign list.
     * If there are groups and user is above a customer, include groups
     *
     * @param array $users
     * @return array
     */
    public function getAssignOptions($users)
    {
        $assign_options = [
            '' => ev_gettext('Any'),
            '-1' => ev_gettext('un-assigned'),
            '-2' => ev_gettext('myself and un-assigned'),
        ];

        $user_groups = User::getGroups($this->usr_id);

        if (Auth::isAnonUser()) {
            unset($assign_options['-2']);
        } elseif (count($user_groups) > 0) {
            $assign_options['-3'] = ev_gettext('myself and my group');
            $assign_options['-4'] = ev_gettext('myself, un-assigned and my group');
        }

        if (Auth::getCurrentRole() > User::ROLE_CUSTOMER && ($groups = Group::getAssocList($this->prj_id))) {
            foreach ($groups as $grp_id => $grp_name) {
                $assign_options["grp:$grp_id"] = ev_gettext('Group') . ': ' . $grp_name;
            }
        }

        return $assign_options + $users;
    }
}
