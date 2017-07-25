#!/usr/bin/php
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

/**
 * A script that changes migrates attachments from one storage backend to another another storage backend.
 *
 * This may take a very long time to run, depending on how much data needs to be migrated.
 *
 * WARNING: Migrating data is a risky business. Make sure you have EVERYTHING backed up before you begin this process.
 */

use Eventum\Attachment\AttachmentManager;
use Eventum\Attachment\StorageManager;

if (!isset($argv[2])) {
    echo sprintf("Usage: %s source_adapter target_adapter\n", $argv[0]);
    exit;
}

if (!in_array('--yes', $argv)) {
    echo "WARNING: Migrating data has risks. Make sure all your data is backed up before continuing. 

Pass '--yes' as the last argument to skip this warning and perform the migration\n";
    exit;
}

list($source_adapter, $target_adapter) = array_slice($argv, 1, 2);

require __DIR__ . '/../init.php';

$chunksize = 100;

echo "Migrating data from $source_adapter to  $target_adapter\n";

$sm = StorageManager::get();

while (true) {
    $sql = "SELECT
                iaf_id,
                iaf_filename,
                iaf_flysystem_path,
                iat_iss_id,
                iat_created_date
            FROM
                {{%issue_attachment_file}},
                {{%issue_attachment}}
            WHERE
                iat_id = iaf_iat_id AND
                iaf_flysystem_path LIKE '$source_adapter://%'
            ORDER BY
                iaf_id ASC
            LIMIT $chunksize";
    $res = DB_Helper::getInstance()->getAll($sql);

    if (empty($res)) {
        echo "No more attachments to migrate\n";
        break;
    }

    foreach ($res as $row) {
        $iaf_id = $row['iaf_id'];
        $filename = $row['iaf_filename'];
        $issue_id = $row['iat_iss_id'];
        $old_path = $row['iaf_flysystem_path'];
        $created_date = $row['iat_created_date'];
        $new_path = AttachmentManager::generatePath($iaf_id, $filename, $issue_id);
        $new_path = str_replace("{$sm->getDefaultAdapter()}://", "{$target_adapter}://", $new_path);

        echo "Moving $iaf_id '{$filename}' from $old_path to $new_path\n";

        try {
            $move_res = $sm->moveFile($old_path, $new_path);
        } catch (\League\Flysystem\Exception $e) {
            echo "\tERROR {$iaf_id}: {$e->getMessage()}\n";
            break 2;
        }

        $sql = 'UPDATE
                    {{%issue_attachment_file}}
                SET
                    iaf_flysystem_path = ?,
                    iaf_file = null
                WHERE
                    iaf_id = ?';
        DB_Helper::getInstance()->query($sql, [$new_path, $iaf_id]);

        if ($target_adapter == 'local') {
            // try to set the timestamp on the filesystem to match what is stored in the database
            $fs_path = str_replace('local://', APP_PATH . '/var/storage/', $new_path);
            touch($fs_path, strtotime($created_date));
        }
    }
}

if ($source_adapter == 'legacy') {
    echo "\nYou might need to run 'OPTIMIZE TABLE issue_attachment_file' to reclaim space from the database\n";
}
