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

use Eventum\Db\Adapter\AdapterInterface;

/*
 * Add minimum role to history table and update old history entries
 * https://github.com/eventum/eventum/pull/88
 */

/** @var AdapterInterface $db */

$db->query('alter table {{%issue_history}} add `his_min_role` tinyint(1) NOT NULL DEFAULT ?', [User::ROLE_VIEWER]);

$res = $db->getAll('select htt_id, htt_role from {{%history_type}}');

foreach ($res as $idx => $row) {
    $params = [$row['htt_role'], $row['htt_id']];
    $db->query('UPDATE {{%issue_history}} SET his_min_role=? WHERE his_htt_id=?', $params);
}
