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

class EventumPreferenceBoolean extends AbstractMigration
{
    public function up(): void
    {
        $this->table('user_preference')
            ->changeColumn('upr_list_refresh_rate', 'integer', ['limit' => self::INT_MEDIUM, 'signed' => false, 'default' => 5])
            ->changeColumn('upr_email_refresh_rate', 'integer', ['limit' => self::INT_MEDIUM, 'signed' => false, 'default' => 5])
            ->changeColumn('upr_auto_append_email_sig', 'boolean', ['default' => false])
            ->changeColumn('upr_auto_append_note_sig', 'boolean', ['default' => false])
            ->changeColumn('upr_auto_close_popup_window', 'boolean', ['default' => true])
            ->changeColumn('upr_relative_date', 'boolean', ['default' => true])
            ->changeColumn('upr_collapsed_emails', 'boolean', ['default' => true])
            ->changeColumn('upr_markdown', 'boolean', ['default' => true])
            ->update();
    }

    public function down(): void
    {
    }
}
