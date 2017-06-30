<?php

use Eventum\Db\AbstractMigration;

class EventumAttachmentsDropStatus extends AbstractMigration
{
    /**
     * No down method since the data for this column is gone
     */
    public function up()
    {
        $this->table('issue_attachment')->removeColumn('iat_status')->update();
    }
}
