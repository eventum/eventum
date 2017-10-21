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

class EventumIssueAssociationPk extends AbstractMigration
{
    public function up()
    {
        /*
         * use raw sql because:
         * - can't use FIRST
         * - can't set primary key: https://github.com/cakephp/phinx/issues/335
         * - can't add primary key with auto increment
         *
        $options = ['before' => 'isa_issue_id', 'signed' => false];
        $table = $this->table('issue_association', ['id' => false, 'primary_key' => 'isa_id']);
        $table->addColumn('isa_id', 'integer', $options);
        $table->addIndex('isa_id', ['type'=>'primary']);
        $this->getPrimaryKey($table)->setIdentity(true);
        $table->update();
         */
        $this->execute(
            'ALTER TABLE `issue_association` ADD `isa_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST'
        );
    }

    public function down()
    {
        $this->execute('ALTER TABLE `issue_association` DROP COLUMN `isa_id`');
    }
}
