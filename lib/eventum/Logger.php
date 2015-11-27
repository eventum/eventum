<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2015 Eventum Team.                                     |
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
// | 51 Franklin Street, Suite 330                                        |
// | Boston, MA 02110-1301, USA.                                          |
// +----------------------------------------------------------------------+

/**
 * @method static Monolog\Logger app() Application log channel
 * @method static Monolog\Logger db() Database log channel
 * @method static Monolog\Logger auth() Auth log channel
 * @method static Monolog\Logger cli() CLI log channel
 */
class Logger extends Monolog\Registry
{
    /**
     * Configure logging for Eventum application.
     *
     * This can be used like:
     *
     * Logger::api()->addError('Sent to $api Logger instance');
     * Logger::application()->addError('Sent to $application Logger instance');
     */
    public static function initialize()
    {
        // Configure it use Eventum timezone
        Monolog\Logger::setTimezone(new DateTimeZone(APP_DEFAULT_TIMEZONE));

        // create 'app' instance, it will be used base of other loggers
        $logfile = self::createFileHandler('eventum.log');
        $app = static::createLogger('app', array(), array())->pushHandler($logfile);

        // setup mail logger if enabled
        $mailer = self::createMailHandler();
        if ($mailer) {
            $app->pushHandler($mailer);
        }

        $app->pushProcessor(new Monolog\Processor\WebProcessor());
        $app->pushProcessor(new Monolog\Processor\MemoryUsageProcessor());
        $app->pushProcessor(new Monolog\Processor\MemoryPeakUsageProcessor());
        $app->pushProcessor(
            function (array $record) {
                $record['extra']['version'] = APP_VERSION;

                return $record;
            }
        );

        // add logger for database
        static::createLogger('db');

        // log auth channel to auth.log
        static::createLogger('auth', array(self::createFileHandler('auth.log')));

        // add cli logger with different output file
        static::createLogger('cli', array(self::createFileHandler('cli.log')));

        static::registerErrorHandler($app);
    }

    /**
     * create php errorhandler, which also logs to php error_log
     *
     * @param Monolog\Logger $app
     */
    private static function registerErrorHandler($app)
    {
        // get base logger
        $logger = clone $app;
        // add extra handler
        $handler = new Monolog\Handler\ErrorLogHandler();
        // set formatter without datetime
        $handler->setFormatter(
            new Monolog\Formatter\LineFormatter('%channel%.%level_name%: %message% %context% %extra%')
        );
        $logger->pushHandler($handler);

        // attach php errorhandler to app logger
        Monolog\ErrorHandler::register($logger);
    }

    /**
     * Helper to create named logger and add it to registry.
     * If handlers or processors not specified, they are taken from 'app' logger.
     *
     * This could be useful, say in LDAP Auth Adapter:
     *
     * $logger = Logger::createLogger('ldap');
     * $logger->error('ldap error')
     *
     * @param string $name
     * @param array $handlers
     * @param array $processors
     * @return \Monolog\Logger
     */
    public static function createLogger($name, $handlers = null, $processors = null)
    {
        if ($handlers === null) {
            $handlers = self::getInstance('app')->getHandlers();
        }
        if ($processors === null) {
            $processors = self::getInstance('app')->getProcessors();
        }

        $logger = new Monolog\Logger($name, $handlers, $processors);

        Monolog\Registry::addLogger($logger);

        return $logger;
    }

    /**
     * Create Handler that logs to a file in APP_LOG_PATH directory
     *
     * @param string $filename
     * @param integer $level The minimum logging level at which this handler will be triggered
     * @return \Monolog\Handler\StreamHandler
     */
    private function createFileHandler($filename, $level = Monolog\Logger::INFO)
    {
        $path = APP_LOG_PATH . '/' . $filename;

        // make files not world readable by default
        $filePermission = 0640;

        // only set the filePermission if the log does not exist, this allows to chmod it later.
        // Monolog keeps insisting the permission if we pass it on existing log files.
        if (file_exists($path)) {
            $filePermission = null;
        }

        return new Monolog\Handler\StreamHandler($path, $level, true, $filePermission, false);
    }

    /**
     * Get mail handler if configured
     *
     * @return \Monolog\Handler\MailHandler
     */
    private static function createMailHandler()
    {
        $setup = Setup::get();
        if ($setup['email_error']['status'] != 'enabled') {
            return null;
        }

        $notify_list = trim($setup['email_error']['addresses']);
        if (!$notify_list) {
            return null;
        }

        // recipient list can be comma separated
        $to = Misc::trim(explode(',', $notify_list));
        $subject = APP_SITE_NAME . ' - Error found!';
        $handler = new Monolog\Handler\NativeMailerHandler(
            $to,
            $subject,
            $setup['smtp']['from'],
            Monolog\Logger::ERROR
        );

        return $handler;
    }
}
