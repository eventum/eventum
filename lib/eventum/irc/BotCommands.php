<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2015 Eventum Team.                                     |
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
 * Class containing IRC Bot command handlers.
 *
 * All public final methods are taken as commands.
 */
class BotCommands extends AbstractBotCommands
{
    /**
     * @param Net_SmartIRC $irc
     * @param Net_SmartIRC_data $data
     */
    final public function help(Net_SmartIRC $irc, Net_SmartIRC_data $data)
    {
        $commands = array(
            'auth' => 'Format is "auth user@example.com password"',
            'clock' => 'Format is "clock [in|out]"',
            'list-clocked-in' => 'Format is "list-clocked-in"',
            'list-quarantined' => 'Format is "list-quarantined"',
        );

        $this->sendResponse($data->nick, 'This is the list of available commands:');
        foreach ($commands as $command => $description) {
            $this->sendResponse($data->nick, "$command: $description");
        }
    }

    /**
     * Format is "auth user@example.com password"
     *
     * @param Net_SmartIRC $irc
     * @param Net_SmartIRC_data $data
     */
    final public function auth(Net_SmartIRC $irc, Net_SmartIRC_data $data)
    {
        if (count($data->messageex) != 3) {
            $this->sendResponse(
                $data->nick,
                'Error: wrong parameter count for "AUTH" command. Format is "!auth user@example.com password".'
            );

            return;
        }

        $email = $data->messageex[1];
        $password = $data->messageex[2];

        // check if the email exists
        if (!Auth::userExists($email)) {
            $this->sendResponse(
                $data->nick, 'Error: could not find a user account for the given email address "$email".'
            );

            return;
        }

        // check if the given password is correct
        if (!Auth::isCorrectPassword($email, $password)) {
            $this->sendResponse(
                $data->nick, 'Error: The email address / password combination could not be found in the system.'
            );

            return;
        }

        // check if the user account is activated
        if (!Auth::isActiveUser($email)) {
            $this->sendResponse(
                $data->nick,
                'Error: Your user status is currently set as inactive. Please contact your local system administrator for further information.'
            );

            return;
        }

        $this->bot->addUser($data, $email);
        $this->sendResponse($data->nick, 'Thank you, you have been successfully authenticated.');
    }

    /**
     * @param Net_SmartIRC $irc
     * @param Net_SmartIRC_data $data
     */
    final public function listAuth(Net_SmartIRC $irc, Net_SmartIRC_data $data)
    {
        foreach ($this->bot->getUsers() as $nickname => $email) {
            $this->sendResponse($data->nick, "$nickname => $email");
        }
    }

    /**
     * Format is "clock [in|out]"
     *
     * @param Net_SmartIRC $irc
     * @param Net_SmartIRC_data $data
     */
    final public function clock(Net_SmartIRC $irc, Net_SmartIRC_data $data)
    {
        if (!$this->isAuthenticated($data)) {
            return;
        }

        switch (count($data->messageex)) {
            case 1:
                break;
            case 2:
                if (in_array($data->messageex[1], array('in', 'out'))) {
                    break;
                }
            // fall through to an error
            default:
                $this->sendResponse(
                    $data->nick, 'Error: wrong parameter count for "CLOCK" command. Format is "!clock [in|out]".'
                );

                return;
        }

        $command = isset($data->messageex[1]) ? $data->messageex[1] : null;

        // FIXME: handle if $email is empty
        $email = $this->bot->getEmailByNickname($data->nick);
        $usr_id = User::getUserIDByEmail($email);

        if ($command == 'in') {
            $res = User::clockIn($usr_id);
        } elseif ($command == 'out') {
            $res = User::clockOut($usr_id);
        } else {
            if (User::isClockedIn($usr_id)) {
                $msg = 'clocked in';
            } else {
                $msg = 'clocked out';
            }
            $this->sendResponse($data->nick, "You are currently $msg.");

            return;
        }

        if ($res == 1) {
            $this->sendResponse($data->nick, "Thank you, you are now clocked $command.");
        } else {
            $this->sendResponse($data->nick, "Error clocking $command.");
        }
    }

    /**
     * Format is "list-clocked-in"
     *
     * @param Net_SmartIRC $irc
     * @param Net_SmartIRC_data $data
     */
    final public function listClockedIn(Net_SmartIRC $irc, Net_SmartIRC_data $data)
    {
        if (!$this->isAuthenticated($data)) {
            return;
        }

        $list = User::getClockedInList();
        if (count($list) == 0) {
            $this->sendResponse($data->nick, 'There are no clocked-in users as of now.');

            return;
        }

        $this->sendResponse($data->nick, 'The following is the list of clocked-in users:');
        foreach ($list as $name => $email) {
            $this->sendResponse($data->nick, "$name: $email");
        }
    }

    /**
     * Format is "list-quarantined"
     *
     * @param Net_SmartIRC $irc
     * @param Net_SmartIRC_data $data
     */
    final public function listQuarantined(Net_SmartIRC $irc, Net_SmartIRC_data $data)
    {
        if (!$this->isAuthenticated($data)) {
            return;
        }

        $list = Issue::getQuarantinedIssueList();
        $count = count($list);
        if ($count == 0) {
            $this->sendResponse($data->nick, 'There are no quarantined issues as of now.');

            return;
        }

        $this->sendResponse($data->nick, "The following are the details of the {$count} quarantined issue(s):");
        foreach ($list as $row) {
            $url = APP_BASE_URL . 'view.php?id=' . $row['iss_id'];
            $msg = sprintf(
                'Issue #%d: %s, Assignment: %s, %s', $row['iss_id'], $row['iss_summary'],
                $row['assigned_users'], $url
            );
            $this->sendResponse($data->nick, $msg);
        }
    }

    /**
     * Method used as a callback to send notification events to the proper
     * recipients.
     *
     * @param   Net_SmartIRC $irc The IRC connection handle
     */
    public function notifyEvents(Net_SmartIRC $irc)
    {
        // check the message table
        $stmt
            = 'SELECT
                    ino_id,
                    ino_iss_id,
                    ino_prj_id,
                    ino_message,
                    ino_target_usr_id,
                    ino_category
                 FROM
                    {{%irc_notice}}
                 LEFT JOIN
                    {{%issue}}
                 ON
                    iss_id=ino_iss_id
                 WHERE
                    ino_status=?';
        $res = DB_Helper::getInstance()->getAll($stmt, array('pending'));
        foreach ($res as $row) {
            if (empty($row['ino_category'])) {
                $row['ino_category'] = $this->default_category;
            }

            // check if this is a targeted message
            if (!empty($row['ino_target_usr_id'])) {
                $nick = $this->bot->getNicknameByUser($row['ino_target_usr_id']);
                if ($nick) {
                    $this->sendResponse($nick, $row['ino_message']);
                }
                // FIXME: why mark it sent if user is not online?
                $this->markEventSent($row['ino_id']);
                continue;
            }

            $channels = $this->bot->getChannels($row['ino_prj_id']);
            if (!$channels) {
                continue;
            }
            foreach ($channels as $channel => $categories) {
                $message = $row['ino_message'];
                if ($row['ino_iss_id'] > 0) {
                    $message .= ' - ' . APP_BASE_URL . 'view.php?id=' . $row['ino_iss_id'];
                } elseif (substr($row['ino_message'], 0, strlen('New Pending Email')) == 'New Pending Email') {
                    $message .= ' - ' . APP_BASE_URL . 'emails.php';
                }
                if (count($this->bot->getProjectsForChannel($channel)) > 1) {
                    // if multiple projects display in the same channel, display project in message
                    $message = '[' . Project::getName($row['ino_prj_id']) . '] ' . $message;
                }
                if (in_array($row['ino_category'], $categories)) {
                    $this->sendResponse($channel, $message);
                }
            }
            $this->markEventSent($row['ino_id']);
        }
    }

    private function markEventSent($ino_id)
    {
        // mark message as sent
        $stmt
            = "UPDATE
                    {{%irc_notice}}
                 SET
                    ino_status='sent'
                 WHERE
                    ino_id=?";
        DB_Helper::getInstance()->query($stmt, array($ino_id));
    }
}
