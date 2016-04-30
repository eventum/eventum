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

use Cascade\Cascade;
use Eventum\Db\DatabaseException;
use Eventum\Monolog\Logger;
use Monolog\Handler\StreamHandler;

class LoggerTest extends TestCase
{
    public static function setUpBeforeClass()
    {
        Logger::initialize();
    }

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

    public function testDbError()
    {
        $this->assertDatabase();
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

    /**
     * Test monolog-cascade project
     */
    public function testCascade()
    {
        // configure your loggers
        Cascade::fileConfig(APP_CONFIG_PATH . '/logger.yml');

        $fooLogger = Cascade::getLogger('foo');
        $this->assertInstanceOf('Monolog\Logger', $fooLogger);

        // undefined logger should do nothing
        $this->assertCount(0, $fooLogger->getHandlers());
        $this->assertCount(0, $fooLogger->getProcessors());

        // this is declared logger
        $myLogger = Cascade::getLogger('myLogger');
        $this->assertInstanceOf('Monolog\Logger', $myLogger);
        $this->assertCount(2, $myLogger->getHandlers());
        $this->assertCount(1, $myLogger->getProcessors());
    }
}
