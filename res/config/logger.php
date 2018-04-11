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

/*
 * This is an base config file for the eventum logger.
 *
 * Do not make changes to this file, they will be overwritten with an Eventum update
 * If you need to make customizations, use config/logger.php
 */

$formatters = [
    'default' => [
        'class' => 'Monolog\\Formatter\\LineFormatter',
        'format' => "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
    ],
    'error_handler' => [
        'class' => 'Monolog\\Formatter\\LineFormatter',
        'format' => '%channel%.%level_name%: %message% %context% %extra%',
    ],
    // https://docs.sentry.io/clients/php/integrations/monolog/
    'raven_formatter' => [
        'class' => 'Monolog\\Formatter\\LineFormatter',
        'format' => '%message% %context% %extra%',
    ],
];

$handlers = [
    'app_log' => [
        'class' => 'Eventum\\Monolog\\StreamHandler',
        'level' => 'INFO',
        'stream' => APP_LOG_PATH . '/eventum.log',
        'filePermission' => 0640,
    ],
    'auth_log' => [
        'class' => 'Eventum\\Monolog\\StreamHandler',
        'level' => 'INFO',
        'stream' => APP_LOG_PATH . '/auth.log',
        'filePermission' => 0640,
    ],
    'cli_log' => [
        'class' => 'Eventum\\Monolog\\StreamHandler',
        'level' => 'INFO',
        'stream' => APP_LOG_PATH . '/cli.log',
        'filePermission' => 0640,
    ],
    'error_handler' => [
        'class' => 'Monolog\\Handler\\ErrorLogHandler',
        'level' => 'INFO',
        'formatter' => 'error_handler',
    ],
    'error_mailer' => [
        'class' => 'Eventum\\Monolog\\MailHandler',
        'level' => 'ERROR',
    ],
    /*
    'slack_reporter' => [
        'class' => 'Monolog\\Handler\\SlackWebhookHandler',
        'level' => 'ERROR',
        'webhookUrl' => '',
        'channel' => '',
        'iconEmoji' => 'boom',
        'useShortAttachment' => true,
        'includeContextAndExtra' => true,
    ],
    // you need to load Raven_Client dependency first:
    // $ composer require sentry/sentry
    'raven_reporter' => [
        'class' => 'Monolog\\Handler\\RavenHandler',
        'level' => 'ERROR',
        'ravenClient' => new Raven_Client(
            'https://xxx:yyy@sentry.io/zzz'
        ),
    ],
    */
];

$processors = [
    'web_processor' => [
        'class' => 'Monolog\\Processor\\WebProcessor',
    ],
    'memory_processor' => [
        'class' => 'Monolog\\Processor\\MemoryUsageProcessor',
    ],
    'memory_peak_processor' => [
        'class' => 'Monolog\\Processor\\MemoryPeakUsageProcessor',
    ],
    'psr_log_processor' => [
        'class' => 'Monolog\\Processor\\PsrLogMessageProcessor',
    ],
    'introspection_processor' => [
        'class' => 'Monolog\\Processor\\IntrospectionProcessor',
    ],
    'eventum_app_info_processor' => [
        'class' => 'Eventum\\Monolog\\AppInfoProcessor',
    ],
];

$default_processors = [
    'web_processor',
    'psr_log_processor',
    'introspection_processor',
    'memory_processor',
    'memory_peak_processor',
    'eventum_app_info_processor',
];

$loggers = [
    'app' => [
        'handlers' => [
            'app_log',
            'error_handler',
            'error_mailer',
//            'slack_reporter',
//            'raven_reporter',
        ],
        'processors' => $default_processors,
    ],
    'auth' => [
        'handlers' => [
            'auth_log',
            'error_handler',
        ],
        'processors' => $default_processors,
    ],
    'cli' => [
        'handlers' => [
            'cli_log',
            'error_handler',
        ],
        'processors' => $default_processors,
    ],
];

return [
    'formatters' => $formatters,
    'handlers' => $handlers,
    'processors' => $processors,
    'loggers' => $loggers,
];
