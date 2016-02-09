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
 * Adds the ability for users to belong in multiple groups
 */

/** @var AdapterInterface $db */

$db->query('CREATE TABLE {{%user_group}} (
  ugr_usr_id int unsigned NOT NULL,
  ugr_grp_id int unsigned NOT NULL,
  ugr_created datetime NOT NULL,
  PRIMARY KEY(ugr_usr_id, ugr_grp_id)
)');

$res = $db->getAll('select usr_id, usr_grp_id from {{%user}} where usr_grp_id IS NOT NULL');
foreach ($res as $row) {
    Group::addUser($row['usr_id'], $row['usr_grp_id']);
}

$db->query('ALTER TABLE {{%user}} DROP COLUMN usr_grp_id');
