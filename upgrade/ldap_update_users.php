<?PHP
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2014 Eventum Team.                                     |
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
// | Authors: Elan Ruusamäe <glen@delfi.ee>                               |
// +----------------------------------------------------------------------+

require_once dirname(__FILE__) . '/../init.php';

/**
 * Get the new user information from the LDAP servers
 */

$new_role = User::ROLE_REPORTER;
$new_projects = array("Support");

$projects = array();
foreach ($new_projects as $project) {
    $projects[] = Project::getID($project);
}

$active_users = array();

//$auth = Auth::getAuthBackend();
$backend = new LDAP_Auth_Backend();
$search = $backend->getUserListing();

while ($entry = $search->shiftEntry()) {
    $uid = $entry->getValue('uid');
    echo "updating: $uid\n";
    if ($uid == "vladimirkja") {
        $values = $entry->getValues();
        var_dump($values);
        die;
    }
}
die;

$ldap = ldap_connect(LDAP_HOST, LDAP_PORT);
ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
$bind_result = ldap_bind($ldap, LDAP_BIND_DN, LDAP_BIND_PASSWORD);
if (true !== $bind_result) {
    print ">Unable to connect to LDAP server";
    exit;
}


$ldap_result = ldap_search(
    $ldap, "ou=People,dc=example,dc=org", "(&(zimbraAccountStatus=active)(!(zimbraHideInGAL=TRUE)))",
    array('mail', 'cn')
);
$info = ldap_get_entries($ldap, $ldap_result);
for ($i = 0; $i < $info["count"]; $i++) {
    $entry = $info[$i];
    $uid = $entry['uid'][0];
    $backend->updateLocalUserFromBackend($uid);
}

