<?php

use Eventum\Db\AbstractMigration;

class EventumStatusChangeDate extends AbstractMigration
{
    public function change()
    {
        $this->table('issue')
            ->addColumn("iss_status_change_date", 'datetime', ['after' => 'iss_last_internal_action_type',
                'null'  => true])
            ->save();


        $this->execute("UPDATE
                            issue
                        SET 
                            iss_status_change_date = IFNULL((
                                SELECT 
                                    MAX(his_created_date) 
                                FROM
                                    issue_history
                                WHERE 
                                    his_iss_id = iss_id AND 
                                    his_htt_id = 9), 
                                iss_created_date);");
    }
}
