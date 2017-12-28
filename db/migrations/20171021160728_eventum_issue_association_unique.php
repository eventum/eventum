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

class EventumIssueAssociationUnique extends AbstractMigration
{
    public function up()
    {
        $this->makeUnique('isa_issue_id', 'isa_associated_id');
        $this->makeUnique('isa_associated_id', 'isa_issue_id');

        $this->updateIndex(true);
    }

    public function down()
    {
        $this->updateIndex(false);
    }

    private function updateIndex($unique)
    {
        $this->table('issue_association')
            ->removeIndex(['isa_issue_id', 'isa_associated_id'])
            ->addIndex(['isa_issue_id', 'isa_associated_id'], ['unique' => $unique])
            ->update();
    }

    /**
     * make pairs unique
     */
    private function makeUnique($field1, $field2)
    {
        $st = $this->query(
            "SELECT isa_id id, $field1 f1, $field2 f2, count(*) c " .
            'FROM issue_association GROUP BY 2,3 HAVING c>1'
        );

        foreach ($st as $row) {
            $this->query(
                "delete from issue_association where isa_id!={$row['id']} and " .
                "$field1={$row['f1']} and $field2={$row['f2']}"
            );
        }
    }
}
