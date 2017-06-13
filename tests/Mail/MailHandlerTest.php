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

namespace Monolog\Handler {
    function mail()
    {
        global $mail;
        $mail[] = func_get_args();
    }
}

namespace Eventum\Test\Mail {

    use Eventum\Monolog\Logger;
    use Eventum\Test\TestCase;
    use Setup;

    /**
     * @group mail
     */
    class MailHandlerTest extends TestCase
    {
        public function testMailHandler()
        {
            $logger = $this->configureMailHandler('enabled');
            $logger->error('error');

            global $mail;
            $this->assertCount(1, $mail);
        }

        public function testMailHandlerDisabled()
        {
            $logger = $this->configureMailHandler('disabled');
            $logger->error('error');

            global $mail;
            $this->assertCount(0, $mail);
        }

        private function configureMailHandler($status)
        {
            global $mail;
            $mail = [];

            $setup = Setup::get();
            $setup['email_error']['status'] = $status;
            $setup['email_error']['addresses'] = 'root@localhost';
            $setup['smtp']['from'] = 'root@locahost';

            Logger::initialize();

            return Logger::app();
        }
    }
}
