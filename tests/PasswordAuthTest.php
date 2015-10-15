<?php

class PasswordAuthTest extends PHPUnit_Framework_TestCase
{
    public function testPasswordHash() {
        $password = "Tr0ub4dour&3";
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $length = Misc::countBytes($hash);
        $this->assertEquals(60, $length);
    }
}
