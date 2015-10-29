<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2011 Anderson.net New Zealand                   |
// | Copyright (c) 2011 - 2015 Eventum Team.                              |
// |                                                                      |
// | This program is free software; you can redistribute it and/or modify |
// | it under the terms of the GNU General Public License as published by |
// | the Free Software Foundation; either version 2 of the License, or    |
// | (at your option) any later version.                                  |
// |                                                                      |
// | This program is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
// | GNU General Public License for more details.                         |
// |                                                                      |
// | You should have received a copy of the GNU General Public License    |
// | along with this program; if not, write to:                           |
// |                                                                      |
// | Free Software Foundation, Inc.                                       |
// | 51 Franklin Street, Suite 330                                        |
// | Boston, MA 02110-1301, USA.                                          |
// +----------------------------------------------------------------------+

/**
 * Class designed to handle adding, removing and viewing authorized repliers for an issue.
 */
class Edit_Reporter
{
    /**
     * Modifies an Issue's Reporter.
     *
     * @param   integer $issue_id The id of the issue.
     * @param   string $fullname The id of the user.
     * @param   boolean $add_history If this should be logged.
     * @return int
     */
    public static function update($issue_id, $email, $add_history = true)
    {
        $email = strtolower(Mail_Helper::getEmailAddress($email));
        $usr_id = User::getUserIDByEmail($email, true);

        // If no valid user found reset to system account
        if (!$usr_id) {
            $usr_id = APP_SYSTEM_USER_ID;
        }

        $sql = 'UPDATE
                    {{%issue}}
                SET
                    iss_usr_id = ?
                WHERE
                    iss_id = ?';

        try {
            DB_Helper::getInstance()->query($sql, array($usr_id, $issue_id));
        } catch (DbException $e) {
            return -1;
        }

        if ($add_history) {
            // TRANSLATORS: %1: email, %2: full name
            $current_usr_id = Auth::getUserID();
            History::add($issue_id, $current_usr_id, 'issue_updated', 'Reporter was changed to {email} by {user}', array(
                'email' => $email,
                'user' => User::getFullName($current_usr_id)
            ));
        }

        // Add new user to notification list
        if ($usr_id > 0) {
            Notification::subscribeEmail($usr_id, $issue_id, $email, Notification::getDefaultActions());
        }

        return 1;
    }
}
