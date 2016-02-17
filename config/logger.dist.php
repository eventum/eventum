<?php
# File: logger.dist.php
# This is an template config file for the eventum setup.
# Setup will process this and save as config/logger.yml.
# You can remove this comment :)
#
# Logger configuration for Eventum

$formatters = array(
    'default' => array(
        'class' => 'Monolog\\Formatter\\LineFormatter',
        'format' => "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
    ),
    'error_handler' => array(
        'class' => 'Monolog\\Formatter\\LineFormatter',
        'format' => '%channel%.%level_name%: %message% %context% %extra%',
    ),
);

$handlers = array(
    'app_log' => array(
        'class' => 'Monolog\\Handler\\StreamHandler',
        'level' => 'INFO',
        'stream' => APP_LOG_PATH . '/eventum.log',
        'filePermission' => 0640,
    ),
    'auth_log' => array(
        'class' => 'Monolog\\Handler\\StreamHandler',
        'level' => 'INFO',
        'stream' => APP_LOG_PATH . '/auth.log',
        'filePermission' => 0640,
    ),
    'cli_log' => array(
        'class' => 'Monolog\\Handler\\StreamHandler',
        'level' => 'INFO',
        'stream' => APP_LOG_PATH . '/cli.log',
        'filePermission' => 0640,
    ),
    'error_handler' => array(
        'class' => 'Monolog\\Handler\\ErrorLogHandler',
        'formatter' => 'error_handler',
    ),
);

$processors = array(
    'web_processor' => array(
        'class' => 'Monolog\\Processor\\WebProcessor',
    ),
    'memory_processor' => array(
        'class' => 'Monolog\\Processor\\MemoryUsageProcessor',
    ),
    'memory_peak_processor' => array(
        'class' => 'Monolog\\Processor\\MemoryPeakUsageProcessor',
    ),
    'psr_log_processor' => array(
        'class' => 'Monolog\\Processor\\PsrLogMessageProcessor',
    ),
    'introspection_processor' => array(
        'class' => 'Monolog\\Processor\\IntrospectionProcessor',
    ),
    'eventum_app_info_processor' => array(
        'class' => 'Eventum\\Monolog\\AppInfoProcessor',
    ),
);

$default_processors = array(
    'web_processor',
    'psr_log_processor',
    'introspection_processor',
    'memory_processor',
    'memory_peak_processor',
    'eventum_app_info_processor',
);
$loggers = array(
    'app' => array(
        'handlers' => array(
            'app_log',
            'error_handler',
        ),
        'processors' => $default_processors,
    ),
    'auth' => array(
        'handlers' => array(
            'auth_log',
            'error_handler',
        ),
        'processors' => $default_processors,
    ),
    'cli' => array(
        'handlers' => array(
            'cli_log',
            'error_handler',
        ),
        'processors' => $default_processors,
    ),
);

return array(
    'formatters' => $formatters,
    'handlers' => $handlers,
    'processors' => $processors,
    'loggers' => $loggers,
);
