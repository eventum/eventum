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

class EventumRemoteLinks extends AbstractMigration
{
    public function change(): void
    {
        $this->table('remote_link', ['id' => false, 'primary_key' => 'rel_id'])
            ->addColumn('rel_id', 'integer', ['length' => 10, 'signed' => false, 'identity' => true])
            ->addColumn('rel_iss_id', 'integer', ['signed' => false])
            ->addColumn('rel_gid', 'string', ['null' => true, 'limit' => self::TEXT_SMALL, 'encoding' => self::ENCODING_ASCII, 'comment' => 'Global Id'])
            ->addColumn('rel_relation', 'string', ['limit' => self::TEXT_SMALL, 'encoding' => self::ENCODING_ASCII, 'comment' => 'Link relationship type'])
            ->addColumn('rel_url', 'text', ['encoding' => self::ENCODING_ASCII, 'limit' => self::TEXT_REGULAR])
            ->addColumn('rel_title', 'string', ['limit' => self::TEXT_SMALL])
            ->addIndex(['rel_id', 'rel_gid'])
            ->create();
    }
}
