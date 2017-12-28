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

require_once __DIR__ . '/../../../init.php';

// creates user accounts for all the customers
$prj_id = 1;

// FIXME: Customer::getAssocList does not exist
$customers = Customer::getAssocList($prj_id);

foreach ($customers as $customer_id => $customer_name) {
    echo "Customer: $customer_name<br />\n";

    $details = Customer::getDetails($prj_id, $customer_id);

    foreach ($details['contacts'] as $contact) {
        echo 'Contact: ' . $contact['first_name'] . ' ' . $contact['last_name'] . ' (' . $contact['email'] . ")<br />\n";
        $contact_id = User::getUserIDByContactID($contact['contact_id']);
        if (empty($contact_id)) {
            $sql = 'INSERT INTO
                        `user`
                    SET
                        usr_created_date = ?,
                        usr_full_name = ?,
                        usr_email = ?,
                        usr_customer_id = ?,
                        usr_customer_contact_id = ?,
                        usr_preferences = ?';
            $params = [
                Date_Helper::getCurrentDateGMT(),
                $contact['first_name'] . ' ' . $contact['last_name'],
                $contact['email'],
                $customer_id,
                $contact['contact_id'],
                // FIXME: usr_preferences needs to be json encoded?
                Prefs::getDefaults([$prj_id]),
            ];
            try {
                DB_Helper::getInstance()->query($sql, $params);
            } catch (DatabaseException $e) {
                echo 'Error inserting user<br />';
            }
            $new_usr_id = DB_Helper::get_last_insert_id();
            Project::associateUser($prj_id, $new_usr_id, User::ROLE_CUSTOMER);
        }
    }
    echo '<hr />';
}
