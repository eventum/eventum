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

/*
 * group commits by same author, same commit message, same data
 * those are likely the same changeset from CVS
 */

use Eventum\Db\Adapter\AdapterInterface;

/** @var AdapterInterface $db */

// assign generated commitid to commits that don't have it
// note: we include issue_id in the checksum as the checkins could had been removed per issue
// and don't want to make removed commits to re-appear because same changeset is shared with two issues
$db->query(
    "CREATE TEMPORARY TABLE isc_commitid
    SELECT
      group_concat(isc_id) isc_id,
      concat('COMMIT_', md5(concat(isc_iss_id, isc_created_date, isc_username, isc_commit_msg))) isc_commitid
    FROM {{%issue_checkin}} WHERE isc_commitid IS NULL
    GROUP BY isc_created_date,isc_username,isc_commit_msg"
);


$res = $db->getAll(
    "SELECT isc_id,isc_commitid FROM isc_commitid"
);
foreach ($res as $row) {
    $db->query(
        "UPDATE {{%issue_checkin}} SET isc_commitid=? WHERE isc_id IN ({$row['isc_id']})", array($row['isc_commitid'])
    );
}
