<?php
require_once 'TestSetup.php';

class IssueLockTest extends PHPUnit_Framework_TestCase {

    /**
     * @group slow
     */
    public function testLock() {
        $issue_id = 1;
        $locker = 'admin';
        $expires = time() + 2;

        $res = Issue_Lock::acquire($issue_id, $locker, $expires);
        $this->assertTrue($res);

        // lock retry, gives false
        $res = Issue_Lock::acquire($issue_id, $locker, $expires);
        $this->assertFalse($res);

        sleep(2);
        // lock has expired
        $res = Issue_Lock::acquire($issue_id, $locker, $expires);
        $this->assertTrue($res);

        $info = Issue_Lock::getInfo($issue_id);
        $this->assertNotEmpty($info);
        $this->assertArrayHasKey('expires', $info);
        $this->assertEquals($expires, $info['expires']);
        $this->assertArrayHasKey('locker', $info);
        $this->assertEquals($locker, $info['locker']);
    }
}
