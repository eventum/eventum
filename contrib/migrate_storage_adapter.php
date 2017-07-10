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
 */

use Eventum\Attachment\AttachmentManager;
use Eventum\Attachment\StorageManager;

if (!isset($argv[2])) {
    throw new InvalidArgumentException(sprintf('Usage: %s source_adapter target_adapter', $argv[0]));
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
                iat_iss_id
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
        exit;
    }

    foreach ($res as $row) {
        $iaf_id = $row['iaf_id'];
        $filename = $row['iaf_filename'];
        $issue_id = $row['iat_iss_id'];
        $old_path = $row['iaf_flysystem_path'];
        $new_path = AttachmentManager::generatePath($iaf_id, $filename, $issue_id);
        $new_path = str_replace("{$sm->getDefaultAdapter()}://", "{$target_adapter}://", $new_path);

        echo "Moving $iaf_id '{$filename}' from $old_path to $new_path\n";

        try {
            $move_res = $sm->moveFile($old_path, $new_path);
        } catch (\League\Flysystem\FileNotFoundException $e) {
            echo "\tERROR {$iaf_id}: {$e->getMessage()}\n";
        }

        $sql = 'UPDATE
                    {{%issue_attachment_file}}
                SET
                    iaf_flysystem_path = ?
                WHERE
                    iaf_id = ?';
        DB_Helper::getInstance()->query($sql, [$new_path, $iaf_id]);
    }
}
