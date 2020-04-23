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

class EventumSupHeaderLength extends AbstractMigration
{
    private const HEADER_LENGTH = 4096;

    /**
     * here's no down() because we only changed column width
     */
    public function up(): void
    {
        $type = self::PHINX_TYPE_STRING;
        $options = [
            'limit' => self::HEADER_LENGTH,
            'default' => '',
            'encoding' => 'utf8',
            'collation' => 'utf8_unicode_ci',
        ];
        $this->table('support_email')
            ->changeColumn('sup_from', $type, $options)
            ->changeColumn('sup_to', $type, $options)
            ->changeColumn('sup_cc', $type, $options)
            ->changeColumn('sup_subject', $type, $options)
            ->save();
    }
}
