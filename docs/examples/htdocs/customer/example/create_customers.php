<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2012 Eventum Team.                              |
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
// | Authors: Bryan Alsdorf <bryan@mysql.com>                             |
// +----------------------------------------------------------------------+

require_once dirname(__FILE__) . '/../../../init.php';

// creates user accounts for all the customers
$prj_id = 1;

$customers = Customer::getAssocList($prj_id);

foreach ($customers as $customer_id => $customer_name) {
    echo "Customer: $customer_name<br />\n";

    $details = Customer::getDetails($prj_id, $customer_id);

    foreach ($details['contacts'] as $contact) {
        echo "Contact: " . $contact['first_name'] . " " . $contact['last_name'] . " (" . $contact['email'] . ")<br />\n";
        $contact_id = User::getUserIDByContactID($contact['contact_id']);
        if (empty($contact_id)) {
            $sql = "INSERT INTO
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
                    SET
                        usr_created_date = '" . Date_Helper::getCurrentDateGMT() . "',
                        usr_full_name = '" . Misc::escapeString($contact['first_name'] . " " . $contact['last_name']) . "',
                        usr_email = '" . $contact['email'] . "',
                        usr_customer_id = " . $customer_id . ",
                        usr_customer_contact_id = " . $contact['contact_id'] . ",
                        usr_preferences = '" . Misc::escapeString(Prefs::getDefaults(array($prj_id))) . "'";
            $res = DB_Helper::getInstance()->query($sql);
            if (PEAR::isError($res)) {
                echo "Error inserting user<br /><pre>";
                print_r($res);
                echo "</pre>";
            }
            $new_usr_id = DB_Helper::get_last_insert_id();
            Project::associateUser($prj_id, $new_usr_id, User::getRoleID("Customer"));
        }
    }
    echo "<hr />";
}
