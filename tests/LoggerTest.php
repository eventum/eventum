<?php

use Monolog\Handler\StreamHandler;

class LoggerTest extends PHPUnit_Framework_TestCase
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
}
