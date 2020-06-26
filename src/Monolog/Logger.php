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

namespace Eventum\Monolog;

use Cascade\Cascade;
use DateTimeZone;
use Eventum\Config\Paths;
use Eventum\DebugBarManager;
use Eventum\ServiceContainer;
use Monolog;
use Monolog\Registry;
use Setup;

/**
 * @method static Monolog\Logger app() Application log channel
 * @method static Monolog\Logger db() Database log channel
 * @method static Monolog\Logger auth() Auth log channel
 * @method static Monolog\Logger cli() CLI log channel
 */
class Logger extends Registry
{
    /**
     * Configure logging for Eventum application.
     *
     * This can be used like:
     *
     * Eventum\Monolog\Logger::api()->addError('Sent to $api Eventum\Monolog\Logger instance');
     * Eventum\Monolog\Logger::application()->addError('Sent to $application Eventum\Monolog\Logger instance');
     */
    public static function initialize(): void
    {
        // Configure it use Eventum timezone
        Monolog\Logger::setTimezone(new DateTimeZone(Setup::getDefaultTimezone()));

        // configure your loggers
        Cascade::fileConfig(self::getConfig());

        // ensure those log channels are present
        static::createLogger('db');
        static::createLogger('auth');
        static::createLogger('cli');

        // attach php errorhandler to app logger
        Monolog\ErrorHandler::register(self::getInstance('app'));
    }

    /**
     * Load and merge logger configs
     *
     * @return array
     */
    private static function getConfig(): array
    {
        // load $setup, so required files could use $setup variable
        $setup = ServiceContainer::getConfig();

        $configPath = Setup::getConfigPath();
        $files = [
            Paths::APP_PATH . '/res/logger.php',
            $configPath . '/logger.php',
        ];
        $config = [];
        foreach ($files as $file) {
            if (!file_exists($file)) {
                continue;
            }
            $res = require $file;
            if ($res !== 1 && is_array($res)) {
                // merge, if it returns array
                // otherwise they might modified $config directly
                $config = array_merge($config, $res);
            }
        }

        return $config;
    }

    /**
     * Helper to create named logger and add it to registry.
     * If handlers or processors not specified, they are taken from 'app' logger.
     *
     * This could be useful, say in LDAP Auth Adapter:
     *
     * $logger = Eventum\Monolog\Logger::createLogger('ldap');
     * $logger->error('ldap error')
     *
     * @param string $name
     * @param array $handlers
     * @param array $processors
     * @return Monolog\Logger
     */
    public static function createLogger($name, $handlers = null, $processors = null): Monolog\Logger
    {
        if (self::hasLogger($name)) {
            return self::getInstance($name);
        }

        if ($handlers === null) {
            $handlers = self::getInstance('app')->getHandlers();
        }
        if ($processors === null) {
            $processors = self::getInstance('app')->getProcessors();
        }

        $logger = new Monolog\Logger($name, $handlers, $processors);

        self::addLogger($logger);
        DebugBarManager::getDebugBarManager()->registerMonolog($logger);

        return $logger;
    }
}
