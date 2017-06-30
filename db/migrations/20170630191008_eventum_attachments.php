<?php

use Eventum\Db\AbstractMigration;

class EventumAttachments extends AbstractMigration
{
    public function change()
    {
        $this->table('issue_attachment')
            ->addColumn('iat_min_role', 'integer', ['after' => 'iat_usr_id', 'length' => '1', 'signed' => false,
                                                      'null' => false, 'default' => 1])
            ->update();
        $this->table('issue_attachment_file')
            ->addColumn('iaf_flysystem_path', 'string', ['length' => 255, 'null' => true ])
            ->update();
    }
}
