<?php

use Eventum\Db\AbstractMigration;

class EventumAttachmentsMigrateMinRole extends AbstractMigration
{
    public function up()
    {
        $this->execute("UPDATE issue_attachment SET iat_min_role = IF(iat_status = 'public', 1, 4)");
    }
}
