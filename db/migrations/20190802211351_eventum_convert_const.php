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

use Eventum\Config\Config;
use Eventum\Db\AbstractMigration;
use Eventum\ServiceContainer;

class EventumConvertConst extends AbstractMigration
{
    public function up(): void
    {
        $unixTimestamp = time();
        $config = ServiceContainer::getConfig();

        $this->convertConstants($config, [
            // new users will use these for default preferences
            // if the user will receive an email when an issue is assigned to him
            'APP_DEFAULT_ASSIGNED_EMAILS' => true,
            // if the user will receive an email when ANY issue is created
            'APP_DEFAULT_NEW_EMAILS' => false,
            'APP_DEFAULT_COPY_OF_OWN_ACTION' => false,
            'APP_DEFAULT_PAGER_SIZE' => 5,
            'APP_DEFAULT_REFRESH_RATE' => 5,
            // timezone for displayed times in web and emails
            'APP_DEFAULT_TIMEZONE' => 'UTC',
            // default day of week start: 0 = sunday; 1 = monday
            'APP_DEFAULT_WEEKDAY' => 1,
            // 'native' or 'php'. Try native first, if you experience strange issues
            // such as language switching randomly, try php
            'APP_GETTEXT_MODE' => 'native',
            // directory where to save routed drafts/notes/emails. use NULL or '' to disable.
            'APP_ROUTED_MAILS_SAVEDIR' => null,
            // define colors used by eventum
            'APP_INTERNAL_COLOR' => '#9C494B',
            // locale used for localized messages
            'APP_DEFAULT_LOCALE' => 'en_US',
            // if full text searching is enabled
            'APP_ENABLE_FULLTEXT' => false,
            'APP_FULLTEXT_SEARCH_CLASS' => 'mysql_fulltext_search',
            // define the user_id of system user
            'APP_SYSTEM_USER_ID' => 1,
            // cookie related constants
            'APP_COOKIE_URL' => '/',
            'APP_COOKIE_DOMAIN' => null,
            'APP_COOKIE' => 'eventum',
            'APP_COOKIE_EXPIRE' => $unixTimestamp + (60 * 60 * 8),
            'APP_PROJECT_COOKIE' => 'eventum_project',
            'APP_PROJECT_COOKIE_EXPIRE' => $unixTimestamp + (60 * 60 * 24 * 30), // 30 days
            'APP_HIDE_CLOSED_STATS_COOKIE' => 'eventum_hide_closed_stats',
            'APP_BASE_URL' => 'http://localhost/',
            'APP_RELATIVE_URL' => '/',
            'APP_HOSTNAME' => 'localhost',
            'APP_NAME' => 'Eventum',
            // used in the subject of notification emails
            'APP_SHORT_NAME' => 'Eventum',
            // email address of anonymous user.
            // if you want anonymous users getting access to your Eventum.
            'APP_ANON_USER' => null,
            /**
             * Path for local overrides:
             * APP_LOCAL_PATH/crm
             * APP_LOCAL_PATH/custom_field
             * APP_LOCAL_PATH/include
             * APP_LOCAL_PATH/partner
             * APP_LOCAL_PATH/templates
             * APP_LOCAL_PATH/workflow
             */
            'APP_LOCAL_PATH' => Setup::getConfigPath(),
            // if set, normal calls to eventum are redirected to a maintenance page while
            // requests to /manage/ still work
            'APP_MAINTENANCE' => false,
        ]);

        // fixup: this should be relative time
        if ($config['cookie_expire'] > $unixTimestamp) {
            $config['cookie_expire'] -= $unixTimestamp;
        }
        if ($config['project_cookie_expire'] > $unixTimestamp) {
            $config['project_cookie_expire'] -= $unixTimestamp;
        }

        // fixup: use proper names
        $config['cookie_path'] = $config['cookie_url'];
        $config['anonymous_user'] = $config['anon_user'];
        unset($config['cookie_url'], $config['anon_user']);

        // fixup: init tool_caption from app_name
        $config['tool_caption'] = $config['tool_caption'] ?: $config['name'];

        Setup::save();
    }

    public function down(): void
    {
    }

    private function convertConstants(Config $setup, $constants): void
    {
        foreach ($constants as $constName => $defaultValue) {
            $value = defined($constName) ? constant($constName) : $defaultValue;
            $key = strtolower(str_replace('APP_', '', $constName));

            // avoid overwriting from previous migrate or value set by setup
            if ($setup[$key] === null) {
                $setup[$key] = $value;
            }
        }
    }
}
