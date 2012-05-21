#!/usr/bin/php
<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
/*
 * Script to query LDAP and try to fill external_id for user based on email
 *
 * This will match users by email and call local system update if it finds a match
 */
require_once 'init.php';

class UserEntry {
    public function __construct($usr) {
        $this->id = $usr['usr_id'];
        $this->email = $usr['usr_email'];
        $this->external_id = $usr['usr_external_id'];
    }

    public function __toString() {
        return $this->id;
    }
}

class LDAP_Wrapper extends LDAP_Auth_Backend {

    public function getByEmail($usr) {
        $filter = Net_LDAP2_Filter::create('mail', 'equals', $usr->email);
        $requested_attributes = array('cn', 'uid', 'mail');
        $search = $this->conn->search($this->config['basedn'], $filter, array('attributes' => $requested_attributes));

        if (PEAR::isError($search)) {
            error_log($entry->getCode(). ": ". $entry->getMessage());
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
        $data = array(
            'password'  =>  '',
            'full_name' =>  $usr->full_name,
            'email'     =>  $usr->email,
            'external_id'   =>  $usr->uid,
        );
        return User::update($usr->id, $data, false);
    }
}

if (APP_AUTH_BACKEND != 'ldap_auth_backend') {
    error_log("You should enable and configure LDAP backend first");
    exit(1);
}

$users = array();
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
