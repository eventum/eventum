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

class EventumAttachmentsMigrate extends AbstractMigration
{
    public function up()
    {
        $this->execute("UPDATE issue_attachment SET iat_min_role = IF(iat_status = 'public', 1, 4)");
        $this->execute("INSERT INTO
                                issue_attachment_file_path
                            (
                                iap_iaf_id,
                                iap_flysystem_path
                            )
                            (
                            SELECT
                                iaf_id,
                                CONCAT('legacy://', iaf_id)
                            FROM
                                issue_attachment_file)
                            ");
    }
}
