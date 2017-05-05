#!/usr/bin/php
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

/*
 * Script to query LDAP and try to fill external_id for user based on email
 *
 * This will match users by email and call local system update if it finds a match
 */

require_once __DIR__ . '/../init.php';

class UserEntry
{
    public function __construct($usr)
    {
        $this->id = $usr['usr_id'];
        $this->email = $usr['usr_email'];
        $this->external_id = $usr['usr_external_id'];
    }

    public function __toString()
    {
        return $this->id;
    }
}

class LDAP_Wrapper extends LDAP_Auth_Backend
{
    public function getByEmail($usr)
    {
        $filter = Net_LDAP2_Filter::create('mail', 'equals', $usr->email);
        $requested_attributes = ['cn', 'uid', 'mail'];
        $search = $this->connect()->search($this->basedn, $filter, ['attributes' => $requested_attributes]);

        if (Misc::isError($search)) {
            $entry = $search;
            error_log($entry->getCode() . ': ' . $entry->getMessage());

            return null;
        }

        if ($search->count() <= 0) {
            return false;
        }

        $entry = $search->current();
        $usr->uid = $entry->get_value('uid');
        $usr->full_name = $entry->get_value('cn');

        return true;
    }

    public function updateLocalUser($usr)
    {
        $data = [
            'full_name' => $usr->full_name,
            'email' => $usr->email,
            'external_id' => $usr->uid,
        ];

        return User::update($usr->id, $data, false);
    }
}

if (strtolower(APP_AUTH_BACKEND) != 'ldap_auth_backend') {
    error_log('You should enable and configure LDAP backend first');
    exit(1);
}

$users = [];
foreach (User::getList() as $entry) {
    $usr = new UserEntry($entry);
    $users[$usr->id] = $usr;
}

$ldap = new LDAP_Wrapper();
foreach ($users as $usr) {
    $details = $ldap->getByEmail($usr);
    if (!$details) {
        continue;
    }
    printf("usr_id #%d %s found: %s (%s)\n", $usr->id, $usr->email, $usr->uid, $usr->full_name);

    // call for update
    $res = $ldap->updateLocalUser($usr);
    if ($res != 1) {
        echo "UPDATE FAILED: $res\n";
    }
}
