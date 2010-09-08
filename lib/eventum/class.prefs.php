<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
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
// | 59 Temple Place - Suite 330                                          |
// | Boston, MA 02111-1307, USA.                                          |
// +----------------------------------------------------------------------+
// | Authors: João Prado Maia <jpm@mysql.com>                             |
// +----------------------------------------------------------------------+


/**
 * Class to handle the business logic related to the user preferences
 * available in the application.
 *
 * @version 1.0
 * @author João Prado Maia <jpm@mysql.com>
 */

class Prefs
{
    /**
     * Method used to get the system-wide default preferences.
     *
     * @access  public
     * @param   array $projects An array of projects this user will have access too.
     * @return  string array of the default preferences
     */
    public function getDefaults($projects = null)
    {
        $prefs = array(
            'receive_assigned_emails' => array(),
            'receive_new_emails'      => array(),
            'timezone'                => Date_Helper::getDefaultTimezone(),
            'week_firstday'           => Date_Helper::getDefaultWeekday(),
            'list_refresh_rate'       => APP_DEFAULT_REFRESH_RATE,
            'emails_refresh_rate'     => APP_DEFAULT_REFRESH_RATE,
            'email_signature'         => '',
            'auto_append_sig'         => 'no',
            'auto_append_note_sig'    => 'no',
        );

        if (is_array($projects)) {
            foreach ($projects as $prj_id) {
                $prefs['receive_assigned_emails'][$prj_id] = APP_DEFAULT_ASSIGNED_EMAILS;
                $prefs['receive_new_emails'][$prj_id] = APP_DEFAULT_NEW_EMAILS;
            }
        }
        return $prefs;
    }


    /**
     * Method used to get the preferences set by a specific user.
     *
     * @access  public
     * @param   integer $usr_id The user ID
     * @return  array The preferences
     */
    public function get($usr_id)
    {
        static $returns;

        $usr_id = Misc::escapeInteger($usr_id);

        if (!empty($returns[$usr_id])) {
            return $returns[$usr_id];
        }

        $stmt = "SELECT
                    usr_preferences
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
                 WHERE
                    usr_id=$usr_id";
        $res = DB_Helper::getInstance()->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return null;
        }

        if ($res) {
            // for empty row there's nothing to unserialize
            $res = unserialize($res);
        }

        // get projects list for user
        $projects = array();
        foreach (Project::getAssocList($usr_id) as $prj_id => $prj_title) {
            $projects[] = $prj_id;
        }

        // merge fetched user prefs with system defaults
        $defaults = self::getDefaults($projects);
        if (empty($res)) {
            // array_merge wants arguments as arrays
            $res = array();
        }
        $res = array_merge($defaults, $res);

        // cache and return
        return $returns[$usr_id] = $res;
    }


    /**
     * Method used to set the preferences for a specific user.
     *
     * @access  public
     * @param   integer $usr_id The user ID
     * @return  integer 1 if the update worked, -1 otherwise
     */
    public function set($usr_id)
    {
        // if the user is trying to upload a new signature, override any changes to the textarea
        if (!empty($_FILES["file_signature"]["name"])) {
            $_POST['signature'] = file_get_contents($_FILES["file_signature"]["tmp_name"]);
        }

        $data = serialize(array(
            'close_popup_windows'     => $_POST['close_popup_windows'],
            'week_firstday'           => $_POST['week_firstday'] == 1 ? 1 : 0,
            'receive_assigned_emails' => $_POST['receive_assigned_emails'],
            'receive_new_emails'      => @$_POST['receive_new_emails'],
            'timezone'                => $_POST['timezone'],
            'list_refresh_rate'       => $_POST['list_refresh_rate'],
            'emails_refresh_rate'     => $_POST['emails_refresh_rate'],
            'email_signature'         => @$_POST['signature'],
            'auto_append_sig'         => @$_POST['auto_append_sig'],
            'auto_append_note_sig'    => @$_POST['auto_append_note_sig']
        ));
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
                 SET
                    usr_preferences='" . Misc::escapeString($data) . "'
                 WHERE
                    usr_id=" . Misc::escapeInteger($usr_id);
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            return 1;
        }
    }
}
