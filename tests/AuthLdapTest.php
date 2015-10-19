<?php

class AuthLdapTest extends PHPUnit_Framework_TestCase
{
    /** @var LDAP_Auth_Backend */
    static $ldap;

    public static function setupBeforeClass() {
        if (getenv('TRAVIS') || getenv('JENKINS_HOME')) {
            self::markTestSkipped('Skip LDAP test on Travis/Jenkins');
        }

        self::$ldap = new LDAP_Auth_Backend();
    }

    public function testLdapSearch()
    {
        $search = self::$ldap->getUserListing();
        while ($entry = $search->shiftEntry()) {
            $uid = $entry->getValue('uid');
            $dn = $entry->dn();

            // if no email, skip completely
            $emails = $entry->get_value('mail', 'all');
            if (!$emails) {
                echo "skip (no email): $uid, $dn\n";
                continue;
            }
            echo "user: $uid, $dn\n";
        }
    }
}
