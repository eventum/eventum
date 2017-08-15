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

class EventumRenameStatusChangeColumn extends AbstractMigration
{
    public function up()
    {
        $this->execute("UPDATE columns_to_display SET ctd_field='status_action_date' WHERE ctd_field='sta_change_date'");
    }

    public function down()
    {
        $this->execute("UPDATE columns_to_display SET ctd_field='sta_change_date' WHERE ctd_field='status_action_date'");
    }
}
