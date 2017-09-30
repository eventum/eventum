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

class RecentActivity
{
    /** @var int */
    private $prj_id;
    /** @var int int */
    private $usr_id;
    /** @var string */
    private $start_date;
    /** @var string */
    private $end_date;
    /** @var array */
    private $activity_types;
    /** @var string */
    private $report_type;
    /** @var string */
    private $unit;
    /** @var string */
    private $amount;
    /** @var string */
    private $developer;
    /** @var string */
    private $sort_order;
    /** @var CRM */
    private $crm;

    public function __construct()
    {
        $this->usr_id = Auth::getUserID();

        if (!Access::canAccessReports($this->usr_id)) {
            throw new LogicException('Invalid role');
        }

        $this->prj_id = Auth::getCurrentProject();
        $this->activity_types = !empty($_REQUEST['activity_types']) ? (array) $_REQUEST['activity_types'] : [];
        $this->report_type = isset($_REQUEST['report_type']) ? (string) $_REQUEST['report_type'] : null;
        $this->unit = $this->getParam('unit', ['hour', 'day']);
        $this->amount = isset($_REQUEST['amount']) ? $_REQUEST['amount'] : null;
        $this->developer = isset($_REQUEST['developer']) ? $_REQUEST['developer'] : null;
        $this->start_date = $this->parseDate(isset($_POST['start']) ? $_POST['start'] : null);
        $this->end_date = $this->parseDate(isset($_POST['end']) ? $_POST['end'] : null);
        $this->sort_order = $this->getParam('sort_order', ['ASC', 'DESC']);

        if (CRM::hasCustomerIntegration($this->prj_id)) {
            $this->crm = CRM::getInstance($this->prj_id);
        }
    }

    public function __invoke(Template_Helper $tpl)
    {
        $units = [
            'hour' => ev_gettext('Hours'),
            'day' => ev_gettext('Days'),
        ];

        $type_list = [
            'phone' => ev_gettext('Phone Calls'),
            'note' => ev_gettext('Notes'),
            'email' => ev_gettext('Email'),
            'draft' => ev_gettext('Drafts'),
            'time' => ev_gettext('Time Tracking'),
            'reminder' => ev_gettext('Reminders'),
        ];

        $tpl->assign([
            'units' => $units,
            'users' => Project::getUserAssocList($this->prj_id, 'active', User::ROLE_CUSTOMER),
            'developer' => $this->usr_id,
            'type_list' => $type_list,
            'activity_types' => $this->activity_types ?: array_keys($type_list),

            'unit' => $this->unit,
            'amount' => $this->amount,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'sort_order' => $this->sort_order,
        ]);

        if (!$this->unit && !$this->amount) {
            return;
        }

        $data = [];
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

        $tpl->assign([
            'data' => $data,
            'developer' => $this->developer,
        ]);
    }

    private function phoneActivity()
    {
        $sql
            = "SELECT
                    `phone_support`.*,
                    phc_title,
                    usr_full_name,
                    iss_summary,
                    sta_color
                FROM
                    `phone_support`,
                    `project_phone_category`,
                    `issue`,
                    `user`,
                    `status`
                WHERE
                    iss_sta_id = sta_id AND
                    phs_phc_id = phc_id AND
                    phs_iss_id = iss_id AND
                    phs_usr_id = usr_id AND
                    iss_prj_id = ? AND\n";
        $params = [$this->prj_id];
        $this->createWhereClause($sql, $params, 'phs_created_date', 'usr_id');
        $res = DB_Helper::getInstance()->getAll($sql, $params);
        $this->processResult($res, 'phs_created_date', 'phs_iss_id');

        return $res;
    }

    private function noteActivity()
    {
        $sql
            = "SELECT
                    `note`.*,
                    usr_full_name,
                    iss_summary,
                    sta_color
                FROM
                    `note`,
                    `issue`,
                    `user`,
                    `status`
                WHERE
                    iss_sta_id = sta_id AND
                    not_iss_id = iss_id AND
                    not_usr_id = usr_id AND
                    iss_prj_id = ? AND\n";
        $params = [$this->prj_id];
        $this->createWhereClause($sql, $params, 'not_created_date', 'not_usr_id');
        $res = DB_Helper::getInstance()->getAll($sql, $params);
        $this->processResult($res, 'not_created_date', 'not_iss_id');

        return $res;
    }

    private function emailActivity()
    {
        $sql
            = "SELECT
                    `support_email`.*,
                    iss_summary,
                    CONCAT(sup_ema_id, '-', sup_id) AS composite_id,
                    sta_color
                FROM
                    `support_email`,
                    `issue`,
                    `status`
                WHERE
                    iss_sta_id = sta_id AND
                    sup_iss_id = iss_id AND
                    iss_prj_id = ? AND\n";
        $params = [$this->prj_id];
        $this->createWhereClause($sql, $params, 'sup_date', 'sup_usr_id');
        $res = DB_Helper::getInstance()->getAll($sql, $params);
        $this->processResult($res, 'sup_date', 'sup_iss_id');

        return $res;
    }

    private function draftActivity()
    {
        $sql
            = "SELECT
                    `email_draft`.*,
                    iss_summary,
                    sta_color
                FROM
                    `email_draft`,
                    `issue`,
                    `status`
                WHERE
                    iss_sta_id = sta_id AND
                    emd_iss_id = iss_id AND
                    iss_prj_id = ? AND\n";
        $params = [$this->prj_id];
        $this->createWhereClause($sql, $params, 'emd_updated_date', 'emd_usr_id');
        $res = DB_Helper::getInstance()->getAll($sql, $params);

        $this->processResult($res, 'emd_updated_date', 'emd_iss_id');
        foreach ($res as &$draft) {
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

        return $res;
    }

    private function timeActivity()
    {
        $sql
            = "SELECT
                    `time_tracking`.*,
                    ttc_title,
                    iss_summary,
                    usr_full_name,
                    sta_color
                FROM
                    `time_tracking`,
                    `time_tracking_category`,
                    `issue`,
                    `user`,
                    `status`
                WHERE
                    iss_sta_id = sta_id AND
                    ttr_iss_id = iss_id AND
                    ttr_ttc_id = ttc_id AND
                    ttr_usr_id = usr_id AND
                    iss_prj_id = ? AND\n";
        $params = [$this->prj_id];
        $this->createWhereClause($sql, $params, 'ttr_created_date', 'ttr_usr_id');
        $res = DB_Helper::getInstance()->getAll($sql, $params);
        $this->processResult($res, 'ttr_created_date', 'ttr_iss_id');
        foreach ($res as &$time) {
            $time['time_spent'] = Misc::getFormattedTime($time['ttr_time_spent'], true);
        }

        return $res;
    }

    private function reminderActivity()
    {
        $sql
            = "SELECT
                    `reminder_history`.*,
                    iss_summary,
                    sta_color,
                    rma_title
                FROM
                    `reminder_history`,
                    `reminder_action`,
                    `issue`,
                    `status`
                WHERE
                    iss_sta_id = sta_id AND
                    rmh_iss_id = iss_id AND
                    rmh_rma_id = rma_id AND
                    iss_prj_id = ? AND\n";
        $params = [$this->prj_id];
        $this->createWhereClause($sql, $params, 'rmh_created_date');
        $res = DB_Helper::getInstance()->getAll($sql, $params);
        $this->processResult($res, 'rmh_created_date', 'rmh_iss_id');

        return $res;
    }

    /**
     * Update SQL with common where clause
     *
     * @param string $sql
     * @param array $params
     * @param string $date_field
     * @param string $user_field
     */
    private function createWhereClause(&$sql, &$params, $date_field, $user_field = null)
    {
        if ($this->report_type == 'recent') {
            $sql .= "$date_field >= DATE_SUB(?, INTERVAL ? {$this->unit})";
            $params[] = Date_Helper::getCurrentDateGMT();
            $params[] = $this->amount;
        } else {
            $sql .= "$date_field BETWEEN ? AND ?";
            $params[] = $this->start_date;
            $params[] = $this->end_date;
        }
        if ($user_field && $this->developer) {
            $sql .= " AND $user_field = ?";
            $params[] = $this->developer;
        }

        $sql .= " ORDER BY $date_field {$this->sort_order}";
    }

    /**
     * @param string $date_field
     * @param string $issue_field
     */
    private function processResult(&$data, $date_field, $issue_field)
    {
        $timezone = Date_Helper::getPreferredTimezone($this->usr_id);
        foreach ($data as &$res) {
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
            $res['date'] = Date_Helper::getFormattedDate($res[$date_field], $timezone);
        }
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

    /**
     * Return Request parameter, optionally validating it against whitelist
     *
     * @param string $key
     * @param array $valid_values
     * @return mixed
     */
    private function getParam($key, $valid_values = null)
    {
        if (!isset($_REQUEST[$key])) {
            return null;
        }

        if (!isset($valid_values)) {
            return $_REQUEST[$key];
        }

        if (is_array($valid_values) && in_array($_REQUEST[$key], $valid_values)) {
            return $_REQUEST[$key];
        }

        return null;
    }
}
