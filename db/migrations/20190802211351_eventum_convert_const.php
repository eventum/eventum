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

use Eventum\Db\AbstractMigration;

class EventumConvertConst extends AbstractMigration
{
    public function up(): void
    {
        $setup = Setup::get();

        $this->convertConstants($setup, [
            // new users will use these for default preferences
            // if the user will receive an email when an issue is assigned to him
            'APP_DEFAULT_ASSIGNED_EMAILS' => true,
            // if the user will receive an email when ANY issue is created
            'APP_DEFAULT_NEW_EMAILS' => false,
            'APP_DEFAULT_COPY_OF_OWN_ACTION' => false,
            'APP_DEFAULT_PAGER_SIZE' => 5,
        ]);

        Setup::save();
    }

    public function down(): void
    {
    }

    private function convertConstants($setup, $constants): void
    {
        foreach ($constants as $constName => $defaultValue) {
            $value = defined($constName) ? constant($constName) : $defaultValue;
            $key = strtolower(str_replace('APP_', '', $constName));

            $setup[$key] = $value;
        }
    }
}
