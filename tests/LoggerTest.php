<?php

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
            DB_Helper::getInstance()->query('here -->?<-- be dragons?', array('param1', 'param2'));
        } catch (DbException $e) {
        }
    }

    /**
     * @test what happens if i just log exception object
     */
    public function testLogException()
    {
        $e = new Exception('It happened');

        Logger::app()->error($e);
        Logger::app()->error($e->getMessage(), array('exception' => $e));
    }

    public function testLogPearException()
    {
        $e = new PEAR_Error('It happened');

        // toString pear error object is not useful:
        // "app.ERROR: It happened []"
        Logger::app()->error($e);

        Logger::app()->error($e->getMessage(), array('debug' => $e->getDebugInfo()));
    }

    public function testCliLog()
    {
        Logger::cli()->info('moo');
    }
}
