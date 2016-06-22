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

/** @var Closure $log */
/** @var AdapterInterface $db */

$db->query('alter table {{%issue_checkin}}
	add isc_commitid varchar(40) binary after isc_iss_id');

// do some cleanup, patch 21 already cleaned up 'NONE' => NULL
$db->query("UPDATE {{%issue_checkin}} SET isc_old_version=NULL WHERE isc_old_version=''");
$db->query("UPDATE {{%issue_checkin}} SET isc_new_version=NULL WHERE isc_new_version=''");

// change git and svn commits first, they contain already commit identifier
// for them changeset id is new_revision property

// git
$db->query('
    UPDATE {{%issue_checkin}} SET isc_commitid=isc_new_version
    WHERE isc_commitid IS NULL AND LENGTH(isc_new_version)=40
');

// svn
$db->query("
    UPDATE {{%issue_checkin}} SET isc_commitid=isc_new_version
    WHERE isc_commitid IS NULL AND isc_new_version IS NOT NULL AND isc_new_version NOT LIKE '%.%'
");

// svn removed files
// the real commit id is old_revision+1 (svn-hook used the exact opposite: old_rev=new_rev-1)
$db->query("
    UPDATE {{%issue_checkin}} SET isc_commitid=isc_old_version+1
    WHERE isc_commitid IS NULL AND isc_new_version IS NULL
    AND isc_old_version IS NOT NULL AND isc_old_version NOT LIKE '%.%'
");

// for the rest (cvs), assign generated commitid to commits that don't have it
// note: we include issue_id in the checksum as the checkins could had been removed per issue
// and don't want to make removed commits to re-appear because same changeset is shared with two issues

// Increase GROUP_CONCAT() length, default length 1024 characters is too short
$db->query('SET SESSION group_concat_max_len = 1000000');

// we ignore date by one minute difference as big commits could take time:
//+---------------------+--------------------------------------------+
//| isc_created_date    | floor(unix_timestamp(isc_created_date)/60) |
//+---------------------+--------------------------------------------+
//| 2013-05-03 13:16:50 |                                   22792936 |
//| 2013-05-03 13:16:50 |                                   22792936 |
//| 2013-05-03 13:16:50 |                                   22792936 |
//| 2013-05-03 13:16:51 |                                   22792936 |
//| 2013-05-03 13:16:51 |                                   22792936 |
// NOTE: use MIN(isc_created_date) later when separating commit and files to different tables

// CVS commitid is 16 byte length base62 encoded random and seems always end with z0
// so we use 14 bytes from md5, and z1 suffix to get similar (but not conflicting) commitid
$db->query(
    "CREATE TEMPORARY TABLE isc_commitid
    SELECT
      group_concat(isc_id) isc_id,
      concat(substr(md5(concat(
          isc_iss_id,
          isc_reponame,
          floor(unix_timestamp(isc_created_date)/60),
          isc_username,
          isc_commit_msg
      )), 1, 14), 'z1') commitid
    FROM {{%issue_checkin}} WHERE isc_commitid IS NULL
    GROUP BY commitid"
);

$res = $db->getAll(
    'SELECT isc_id,commitid FROM isc_commitid'
);
$count = count($res);
$log("Updating $count changesets");

foreach ($res as $row) {
    $db->query(
        "UPDATE {{%issue_checkin}} SET isc_commitid=? WHERE isc_id IN ({$row['isc_id']})", [$row['commitid']]
    );
}
