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

use Eventum\Db\DatabaseException;

/**
 * Class designed to handle adding, removing and viewing authorized repliers for an issue.
 */
class Edit_Reporter
{
    /**
     * Modifies an Issue's Reporter.
     *
     * @param   int $issue_id the id of the issue
     * @param string $email
     * @param   bool $add_history if this should be logged
     * @return int
     */
    public static function update($issue_id, $email, $add_history = true)
    {
        $email = Mail_Helper::getEmailAddress($email);
        $usr_id = User::getUserIDByEmail($email, true);

        // If no valid user found reset to system account
        if (!$usr_id) {
            $usr_id = APP_SYSTEM_USER_ID;
        }

        $sql = 'UPDATE
                    `issue`
                SET
                    iss_usr_id = ?
                WHERE
                    iss_id = ?';

        try {
            DB_Helper::getInstance()->query($sql, [$usr_id, $issue_id]);
        } catch (DatabaseException $e) {
            return -1;
        }

        if ($add_history) {
            // TRANSLATORS: %1: email, %2: full name
            $current_usr_id = Auth::getUserID();
            History::add($issue_id, $current_usr_id, 'issue_updated', 'Reporter was changed to {email} by {user}', [
                'email' => $email,
                'user' => User::getFullName($current_usr_id),
            ]);
        }

        // Add new user to notification list
        if ($usr_id > 0) {
            Notification::subscribeEmail($usr_id, $issue_id, $email, Notification::getDefaultActions());
        }

        return 1;
    }
}
