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

class EventumDbCharsetConfig extends AbstractMigration
{
    public function up(): void
    {
        $config = Setup::get();
        $config['database']['charset'] = $this->getCharset();

        Setup::save();
    }

    /**
     * Get charset suitable for PDO mysql driver
     *
     * @return string
     */
    protected function getCharset()
    {
        // no dash variant listed, blindly reap "UTF-8" to "UTF8"
        // http://dev.mysql.com/doc/refman/5.0/en/charset-charsets.html
        return strtolower(str_replace('-', '', APP_CHARSET));
    }
}
