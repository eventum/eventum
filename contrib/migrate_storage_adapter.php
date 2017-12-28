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
use Eventum\Db\Adapter\AdapterInterface;
use Symfony\Component\Console\Output\OutputInterface;

require_once __DIR__ . '/../init.php';

$app = new Silly\Application();
$app->command(Command::USAGE, [new Command(), 'execute']);
$app->setDefaultCommand(Command::DEFAULT_COMMAND, true);
$app->run();

class Command
{
    const DEFAULT_COMMAND = 'migrate:attachments';
    const USAGE = self::DEFAULT_COMMAND . ' [source_adapter] [target_adapter] [--chunksize=] [--yes]';

    /** @var OutputInterface */
    private $output;

    /** @var AdapterInterface */
    private $db;

    /** @var StorageManager */
    private $sm;

    /** @var string */
    private $source_adapter;

    /** @var string */
    private $target_adapter;

    /** @var int */
    private $chunksize;

    public function execute(OutputInterface $output, $source_adapter, $target_adapter, $chunksize = 100, $yes)
    {
        $this->output = $output;
        $this->assertInput($source_adapter, $target_adapter, $yes);

        $this->source_adapter = $source_adapter;
        $this->target_adapter = $target_adapter;
        $this->chunksize = (int)$chunksize;

        $this->db = DB_Helper::getInstance();
        $this->sm = StorageManager::get();
        $this->migrateAttachments();
        $this->postUpgradeNotice();
    }

    private function migrateAttachments()
    {
        $this->output->writeln(
            "Migrating data from '{$this->source_adapter}://' to '{$this->target_adapter}://'"
        );

        while (true) {
            $files = $this->getChunk();
            if (empty($files)) {
                echo "No more attachments to migrate\n";
                break;
            }

            foreach ($files as $file) {
                $this->moveFile($file);
            }
        }
    }

    private function moveFile($file)
    {
        $iaf_id = $file['iaf_id'];
        $filename = $file['iaf_filename'];
        $issue_id = $file['iat_iss_id'];
        $old_path = $file['iap_flysystem_path'];
        $new_path = AttachmentManager::generatePath($iaf_id, $filename, $issue_id);
        $new_path = str_replace("{$this->sm->getDefaultAdapter()}://", "{$this->target_adapter}://", $new_path);

        $this->output->writeln("Moving $iaf_id '{$filename}' from $old_path to $new_path");

        // throws League\Flysystem\Exception
        // we let it abort whole process
        $this->sm->moveFile($old_path, $new_path);

        $sql = 'UPDATE
                    `issue_attachment_file_path`
                SET
                    iap_flysystem_path = ?
                WHERE
                    iap_iaf_id = ?';
        DB_Helper::getInstance()->query($sql, [$new_path, $iaf_id]);

        if ($this->target_adapter === 'local') {
            // try to set the timestamp on the filesystem to match what is stored in the database
            $fs_path = str_replace('local://', StorageManager::STORAGE_PATH, $new_path);
            $created_date = strtotime($file['iat_created_date']);
            touch($fs_path, $created_date);
        }
    }

    private function getChunk()
    {
        $sql
            = "SELECT
                iaf_id,
                iaf_filename,
                iap_flysystem_path,
                iat_iss_id,
                iat_created_date
            FROM
                `issue_attachment_file`,
                `issue_attachment_file_path`,
                `issue_attachment`
            WHERE
                iap_iaf_id = iaf_id AND
                iat_id = iaf_iat_id AND
                iap_flysystem_path LIKE '{$this->source_adapter}://%'
            ORDER BY
                iaf_id ASC
            LIMIT {$this->chunksize}";

        return $this->db->getAll($sql);
    }

    private function assertInput($source_adapter, $target_adapter, $migrate)
    {
        if (!$migrate) {
            throw new RuntimeException(
                'WARNING: Migrating data has risks. ' .
                "Make sure all your data is backed up before continuing.\n" .

                "Pass '--yes' argument to skip this warning " .
                'and perform the migration.'
            );
        }

        if (!$source_adapter || !$target_adapter) {
            throw new RuntimeException('Must specify source and target adapters');
        }
    }

    private function postUpgradeNotice()
    {
        if ($this->source_adapter === 'legacy') {
            $message = "You might need to run 'OPTIMIZE TABLE issue_attachment_file' " .
                'to reclaim space from the database';
            $this->output->writeln("<error>$message</error>");
        }
    }
}
