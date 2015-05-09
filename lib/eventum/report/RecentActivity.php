<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2015 Eventum Team.                              |
// |                                                                      |
// | This program is free software; you can redistribute it and/or modify |
// | it under the terms of the GNU General Public License as published by |
// | the Free Software Foundation; either version 2 of the License, or    |
// | (at your option) any later version.                                  |
// |                                                                      |
// | This program is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
// | GNU General Public License for more details.                         |
// |                                                                      |
// | You should have received a copy of the GNU General Public License    |
// | along with this program; if not, write to:                           |
// |                                                                      |
// | Free Software Foundation, Inc.                                       |
// | 51 Franklin Street, Suite 330                                        |
// | Boston, MA 02110-1301, USA.                                          |
// +----------------------------------------------------------------------+
// | Authors: Bryan Alsdorf <bryan@mysql.com>                             |
// | Authors: Elan Ruusamäe <glen@delfi.ee>                               |
// +----------------------------------------------------------------------+

class RecentActivity
{
    /** @var int */
    private $prj_id;
    /** @var int int */
    private $usr_id;
    /** @var string */
    private $start_date;
    /** @var  string */
    private $end_date;
    /** @var array */
    private $activity_types;
    /** @var string */
    private $unit;
    /** @var string */
    private $amount;
    /** @var string */
    private $developer;
    /** @var CRM */
    private $crm;

    public function __construct() {
        $this->usr_id = Auth::getUserID();

        if (!Access::canAccessReports($this->usr_id)) {
            throw new LogicException('Invalid role');
        }

        $this->prj_id = Auth::getCurrentProject();
        $this->activity_types = !empty($_REQUEST['activity_types']) ? (array)$_REQUEST['activity_types'] : array();
        $this->unit = isset($_REQUEST['unit']) ? $_REQUEST['unit'] : null;
        $this->amount = isset($_REQUEST['amount']) ? $_REQUEST['amount'] : null;
        $this->developer = isset($_REQUEST['developer']) ? $_REQUEST['developer'] : null;
        $this->start_date = $this->parseDate(isset($_POST['start']) ? $_POST['start'] : null);
        $this->end_date = $this->parseDate(isset($_POST['end']) ? $_POST['end'] : null);

        if (CRM::hasCustomerIntegration($this->prj_id)) {
            $this->crm = CRM::getInstance($this->prj_id);
        }
    }

    public function __invoke(Template_Helper $tpl)
    {

        $units = array(
            'hour' => 'Hours',
            'day' => 'Days',
        );

        $type_list = array(
            'phone' => 'Phone Calls',
            'note' => 'Notes',
            'email' => 'Email',
            'draft' => 'Drafts',
            'time' => 'Time Tracking',
            'reminder' => 'Reminders',
        );

        $tpl->assign(array(
            'units' => $units,
            'users' => Project::getUserAssocList($this->prj_id, 'active', User::getRoleID('Customer')),
            'developer' => $this->usr_id,
            'type_list' => $type_list,
            'activity_types' => $this->activity_types ?: array_keys($type_list),

            'unit' => $this->unit,
            'amount' => $this->amount,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,

            'sort_order' => isset($_REQUEST['sort_order']) ? $_REQUEST['sort_order'] : null,
        ));

        if (!$this->unit && !$this->amount) {
            return;
        }

        $data = array();
        if (in_array('phone', $this->activity_types)) {
            $data['phone'] = $this->phoneActivity();
        }

        if (in_array('note', $this->activity_types)) {
            $data['note'] = $this->noteActivity();
        }

        if (in_array('email', $this->activity_types)) {
            $data['email'] = $this->emailActivity();
        }

        if (in_array('draft', $this->activity_types)) {
            $data['draft'] = $this->draftActivity();
        }

        if (in_array('time', $this->activity_types)) {
            $data['time'] = $this->timeActivity();
        }

        if (!$this->developer && in_array('reminder', $this->activity_types)) {
            $data['reminder'] = $this->reminderActivity();
        }

        $tpl->assign(array(
            'data' => $data,
            'developer' => $this->developer,
        ));
    }

    private function phoneActivity()
    {
        $sql
            = "SELECT
                    {{%phone_support}}.*,
                    phc_title,
                    usr_full_name,
                    iss_summary,
                    sta_color
                FROM
                    {{%phone_support}},
                    {{%project_phone_category}},
                    {{%issue}},
                    {{%user}},
                    {{%status}}
                WHERE
                    iss_sta_id = sta_id AND
                    phs_phc_id = phc_id AND
                    phs_iss_id = iss_id AND
                    phs_usr_id = usr_id AND
                    iss_prj_id = ? AND\n";
        $sql .= $this->createWhereClause('phs_created_date', 'usr_id');
        $res = DB_Helper::getInstance()->getAll($sql, array($this->prj_id));
        return $this->processResult($res, 'phs_created_date', 'phs_iss_id');
    }

    private function noteActivity()
    {
        $sql
            = "SELECT
                    {{%note}}.*,
                    usr_full_name,
                    iss_summary,
                    sta_color
                FROM
                    {{%note}},
                    {{%issue}},
                    {{%user}},
                    {{%status}}
                WHERE
                    iss_sta_id = sta_id AND
                    not_iss_id = iss_id AND
                    not_usr_id = usr_id AND
                    iss_prj_id = ? AND\n";
        $sql .= $this->createWhereClause('not_created_date', 'not_usr_id');
        $res = DB_Helper::getInstance()->getAll($sql, array($this->prj_id));
        return $this->processResult($res, 'not_created_date', 'not_iss_id');
    }

    private function emailActivity()
    {
        $sql
            = "SELECT
                    {{%support_email}}.*,
                    iss_summary,
                    CONCAT(sup_ema_id, '-', sup_id) AS composite_id,
                    sta_color
                FROM
                    {{%support_email}},
                    {{%issue}},
                    {{%status}}
                WHERE
                    iss_sta_id = sta_id AND
                    sup_iss_id = iss_id AND
                    iss_prj_id = ? AND\n";
        $sql .= $this->createWhereClause('sup_date', 'sup_usr_id');
        $res = DB_Helper::getInstance()->getAll($sql, array($this->prj_id));
        return $this->processResult($res, 'sup_date', 'sup_iss_id');
    }

    private function draftActivity()
    {
        $sql
            = "SELECT
                    {{%email_draft}}.*,
                    iss_summary,
                    sta_color
                FROM
                    {{%email_draft}},
                    {{%issue}},
                    {{%status}}
                WHERE
                    iss_sta_id = sta_id AND
                    emd_iss_id = iss_id AND
                    iss_prj_id = ? AND\n";
        $sql .= $this->createWhereClause('emd_updated_date', 'emd_usr_id');
        $res = DB_Helper::getInstance()->getAll($sql, array($this->prj_id));

        $data = $this->processResult($res, 'emd_updated_date', 'emd_iss_id');
        foreach ($data as &$draft) {
            if (!empty($draft['emd_unknown_user'])) {
                $draft['from'] = $draft['emd_unknown_user'];
            } else {
                $draft['from'] = User::getFromHeader($draft['emd_usr_id']);
            }
            list($draft['to']) = Draft::getEmailRecipients($draft['emd_id']);
            if (empty($draft['to'])) {
                $draft['to'] = 'Notification List';
            }
        }
        return $data;
    }

    private function timeActivity()
    {
        $sql
            = "SELECT
                    {{%time_tracking}}.*,
                    ttc_title,
                    iss_summary,
                    usr_full_name,
                    sta_color
                FROM
                    {{%time_tracking}},
                    {{%time_tracking_category}},
                    {{%issue}},
                    {{%user}},
                    {{%status}}
                WHERE
                    iss_sta_id = sta_id AND
                    ttr_iss_id = iss_id AND
                    ttr_ttc_id = ttc_id AND
                    ttr_usr_id = usr_id AND
                    iss_prj_id = ? AND\n";
        $sql .= $this->createWhereClause('ttr_created_date', 'ttr_usr_id');
        $res = DB_Helper::getInstance()->getAll($sql, array($this->prj_id));
        $data = $this->processResult($res, 'ttr_created_date', 'ttr_iss_id');
        foreach ($data as &$time) {
            $time['time_spent'] = Misc::getFormattedTime($time['ttr_time_spent'], true);
        }
        return $data;
    }

    private function reminderActivity() {
        $sql
            = "SELECT
                    {{%reminder_history}}.*,
                    iss_summary,
                    sta_color,
                    rma_title
                FROM
                    {{%reminder_history}},
                    {{%reminder_action}},
                    {{%issue}},
                    {{%status}}
                WHERE
                    iss_sta_id = sta_id AND
                    rmh_iss_id = iss_id AND
                    rmh_rma_id = rma_id AND
                    iss_prj_id = ? AND\n";
        $sql .= $this->createWhereClause('rmh_created_date');
        $res = DB_Helper::getInstance()->getAll($sql, array($this->prj_id));
        return $this->processResult($res, 'rmh_created_date', 'rmh_iss_id');
    }

    private function createWhereClause($date_field, $user_field = false)
    {
        $sql = '';
        if ($_REQUEST['report_type'] == 'recent') {
            $sql .= "$date_field >= DATE_SUB('" . Date_Helper::getCurrentDateGMT() . "', INTERVAL " . Misc::escapeInteger(
                    $_REQUEST['amount']
                ) . ' ' . Misc::escapeString($_REQUEST['unit']) . ')';
        } else {
            $sql .= "$date_field BETWEEN '{$this->start_date}' AND '{$this->end_date}'";
        }
        if ($user_field != false && !empty($_REQUEST['developer'])) {
            $sql .= " AND $user_field = " . Misc::escapeString($_REQUEST['developer']);
        }
        $sql .= " ORDER BY $date_field " . Misc::escapeString($_REQUEST['sort_order']);

        return $sql;
    }

    private function processResult($results, $date_field, $issue_field)
    {
        $data = array();
        foreach ($results as &$res) {
            if (!Issue::canAccess($res[$issue_field], $this->usr_id)) {
                continue;
            }
            $res['customer'] = null;
            if ($this->crm) {
                try {
                    $customer = $this->crm->getCustomer(Issue::getCustomerID($res[$issue_field]));
                    $res['customer'] = $customer->getName();
                } catch (CRMException $e) {
                }
            }
            $res['date'] = Date_Helper::getFormattedDate($res[$date_field], Date_Helper::getPreferredTimezone($this->usr_id));
            // need to decode From:, To: mail headers
            if (isset($res['sup_from'])) {
                $res['sup_from'] = Mime_Helper::fixEncoding($res['sup_from']);
            }
            if (isset($res['sup_to'])) {
                $res['sup_to'] = Mime_Helper::fixEncoding($res['sup_to']);
            }

            $data[] = $res;
        }

        return $data;
    }

    private function parseDate($struct)
    {
        if (!$struct || !is_array($struct)) {
            return false;
        }

        if ($struct['Year'] != 0 && $struct['Month'] != 0 && $struct['Day'] != 0) {
            return implode('-', $struct);
        }

        return false;
    }
}
