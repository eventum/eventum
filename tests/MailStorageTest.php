<?php

class MailStorageTest extends PHPUnit_Framework_TestCase
{
    /** @var array */
    private $account;

    public function setUp()
    {
        $setup = &Setup::load();

        if (!isset($setup['imap_account'])) {
            $this->markTestSkipped("Define 'imap_account' array in setup.php for testing");
        }

        /*
         * It should be something like:
          'imap_account' => array(
             'ema_hostname' => 'localhost',
             'ema_port' => 143,
             'ema_type' => 'imap/notls',
             'ema_folder' => 'INBOX',
             'ema_username' => '',
             'ema_password' => '',
           )
        */
        $this->account = $setup['imap_account'];
    }

    public function test1()
    {
        $mbox = new MailStorage($this->account);
    }
}
