<?php

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
