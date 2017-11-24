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

class SetupCest
{
    public function _before(AcceptanceTester $I)
    {
        $I->deleteSetupConfig($I);
    }

    public function setup(AcceptanceTester $I, \Page\Setup $setupPage)
    {
        $I->am('Administrator');
        $I->wantTo('Register Eventum');

        $setupPage->dontSeeLogin();
        $setupPage->shouldSeeInstallForm();

        $rel_url = $I->grabValueFrom('input[name=relative_url]');

        $params = [
            'cat' => 'install',
            'hostname' => 'localhost',
            'relative_url' => $rel_url,
            'db_hostname' => 'localhost',
            'db_name' => 'e',
            'create_db' => 'yes',
            'drop_tables' => 'yes',
            'db_username' => 'mysql',
            'db_password' => '',
            'eventum_user' => '',
            'eventum_password' => '',
            'default_timezone' => 'Europe/Tallinn',
            'default_weekday' => '1',
            'setup' => [
                'smtp' => [
                    'from' => 'root@localhost',
                    'host' => 'localhost',
                    'port' => '25',
                    'auth' => '0',
                ],
            ],
            // Cest only, not supported by setup yet
            'admin_user' => 'admin@example.com',
            'admin_password' => 'admin',
        ];
        $setupPage->setup($params);
    }
}
