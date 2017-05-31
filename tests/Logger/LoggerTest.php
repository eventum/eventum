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

namespace Eventum\Test\Logger;

use DB_Helper;
use Eventum\Db\DatabaseException;
use Eventum\Monolog\Logger;
use Eventum\Test\TestCase;
use Exception;
use Monolog;
use Monolog\Handler\StreamHandler;
use PEAR_Error;

/**
 * @group logger
 */
class LoggerTest extends TestCase
{
    public function testLogger()
    {
        // create a log channel
        $log = new Monolog\Logger('eventum');
        $logfile = APP_LOG_PATH . '/test.log';
        $log->pushHandler(new StreamHandler($logfile, Monolog\Logger::WARNING));

        // add records to the log
        $log->addWarning('Foo');
        $log->addError('Bar');
    }

    public function testLoggerRegistry()
    {
        Logger::app()->addError('Sent to $app Logger instance');
        Logger::db()->addError('Sent to $db Logger instance');
    }

    public function testLoggerCreateLogger()
    {
        $logger = Logger::createLogger('ldap');
        $logger->error('ldap error 1');
        $logger->debug('ldap debug');
    }

    /**
     * @group db
     */
    public function testDbError()
    {
        try {
            DB_Helper::getInstance()->query('here -->?<-- be dragons?', ['param1', 'param2']);
        } catch (DatabaseException $e) {
        }
    }

    /**
     * @test what happens if i just log exception object
     */
    public function testLogException()
    {
        $e = new Exception('It happened');

        Logger::app()->error($e);
        Logger::app()->error($e->getMessage(), ['exception' => $e]);
    }

    public function testLogPearException()
    {
        $e = new PEAR_Error('It happened');

        // toString pear error object is not useful:
        // "app.ERROR: It happened []"
        Logger::app()->error($e);

        Logger::app()->error($e->getMessage(), ['debug' => $e->getDebugInfo()]);
    }

    public function testCliLog()
    {
        Logger::cli()->info('moo');
    }
}
