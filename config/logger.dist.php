<?php
# File: logger.dist.yml
# This is an template config file for the eventum setup.
# Setup will process this and save as config/logger.yml.
# You can remove this comment :)
#
# Logger configuration for Eventum

return array(
    'formatters' => array(
        'default' => array(
            'class' => 'Monolog\\Formatter\\LineFormatter',
            'format' => "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
        ),
        'error_handler' => array(
            'class' => 'Monolog\\Formatter\\LineFormatter',
            'format' => '%channel%.%level_name%: %message% %context% %extra%',
        ),
    ),
    'handlers' => array(
        'app_log' => array(
            'class' => 'Monolog\\Handler\\StreamHandler',
            'level' => 'INFO',
            'formatter' => 'default',
            'stream' => '../var/log/eventum.log',
            'filePermission' => 0640,
        ),
        'auth_log' => array(
            'class' => 'Monolog\\Handler\\StreamHandler',
            'level' => 'INFO',
            'formatter' => 'default',
            'stream' => '../var/log/auth.log',
            'filePermission' => 0640,
        ),
        'cli_log' => array(
            'class' => 'Monolog\\Handler\\StreamHandler',
            'level' => 'INFO',
            'formatter' => 'default',
            'stream' => '../var/log/cli.log',
            'filePermission' => 0640,
        ),
        'error_handler' => array(
            'class' => 'Monolog\\Handler\\ErrorLogHandler',
            'formatter' => 'error_handler',
        ),
    ),
    'processors' => array(
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
    ),
    'loggers' => array(
        'app' => array(
            'handlers' => array(
                'app_log',
                'error_handler',
            ),
            'processors' => array(
                'web_processor',
                'psr_log_processor',
                'introspection_processor',
                'memory_processor',
                'memory_peak_processor',
                'eventum_app_info_processor',
            ),
        ),
        'auth' => array(
            'handlers' => array(
                'auth_log',
                'error_handler',
            ),
            'processors' => array(
                'web_processor',
                'psr_log_processor',
                'introspection_processor',
                'memory_processor',
                'memory_peak_processor',
                'eventum_app_info_processor',
            ),
        ),
        'cli' => array(
            'handlers' => array(
                'cli_log',
                'error_handler',
            ),
            'processors' => array(
                'web_processor',
                'psr_log_processor',
                'introspection_processor',
                'memory_processor',
                'memory_peak_processor',
                'eventum_app_info_processor',
            ),
        ),
    ),
);
