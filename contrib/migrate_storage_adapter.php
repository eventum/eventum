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
use Eventum\Console\Command\Command as BaseCommand;
use Eventum\Db\Adapter\AdapterInterface;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

require_once __DIR__ . '/../init.php';

class Command extends BaseCommand
{
    const DEFAULT_COMMAND = 'migrate:attachments';
    const USAGE = self::DEFAULT_COMMAND . ' [source_adapter] [target_adapter] [--chunksize=] [--yes]';

    /** @var AdapterInterface */
    private $db;

    /** @var StorageManager */
    private $sm;

    /** @var string */
    private $source_adapter;

    /** @var string */
    private $target_adapter;

    public function execute(OutputInterface $output, $source_adapter, $target_adapter, $chunksize = 100, $yes)
    {
        $this->output = $output;
        $this->assertInput($source_adapter, $target_adapter, $yes);

        $this->source_adapter = $source_adapter;
        $this->target_adapter = $target_adapter;

        $this->db = DB_Helper::getInstance();
        $this->sm = StorageManager::get();
        $this->migrateAttachments((int)$chunksize);
        $this->postUpgradeNotice();
    }

    private function migrateAttachments($chunkSize)
    {
        $this->writeln("Migrating data from '{$this->source_adapter}://' to '{$this->target_adapter}://'");
        $this->writeln('Preparing temporary table. Please wait...');
        $total = $this->prepareTemporaryTable();
        $this->writeln("Moving $total file(s)");

        if (!$total) {
            $this->writeln('Nothing to migrate');

            return;
        }

        ProgressBar::setFormatDefinition('custom', ' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s% (%id%: %filename%)');
        $progressBar = new ProgressBar($this->output, $total);
        $progressBar->setFormat('custom');
        $progressBar->start();

        for ($i = 0, $nchunks = ceil($total / $chunkSize); $i < $nchunks; $i++) {
            $files = $this->getChunk($chunkSize);
            if (empty($files)) {
                break;
            }

            foreach ($files as $file) {
                $progressBar->setMessage($file['iaf_id'], 'id');
                $progressBar->setMessage($file['iap_flysystem_path'], 'filename');
                $this->moveFile($file);
                $progressBar->advance();
            }
        }

        $progressBar->finish();
        $this->writeln('');
    }

    private function moveFile($file)
    {
        $iaf_id = $file['iaf_id'];
        $filename = $file['iaf_filename'];
        $issue_id = $file['iat_iss_id'];
        $old_path = $file['iap_flysystem_path'];
        $file_path = AttachmentManager::generatePath($iaf_id, $filename, $issue_id);
        $new_path = str_replace("{$this->sm->getDefaultAdapter()}://", "{$this->target_adapter}://", $file_path);

        // throws League\Flysystem\Exception
        // we let it abort whole process
        $this->sm->moveFile($old_path, $new_path);
        $this->moveFileDatabase($iaf_id, $new_path);
        $this->touchLocalFile($new_path, $file);
    }

    /**
     * Try to set the timestamp on the filesystem to match what is stored in the database
     *
     * @param string $path
     * @param array $file
     */
    private function touchLocalFile($path, array $file)
    {
        /** @var Filesystem $fs */
        $fs = $this->sm->getFile($path)->getFilesystem();
        $adapter = $fs->getAdapter();
        if (!$adapter instanceof Local) {
            return;
        }

        $filesystemPath = $adapter->applyPathPrefix(str_replace('local://', '', $path));

        $date = new DateTime($file['iat_created_date'], new DateTimeZone('UTC'));
        $created_date = $date->getTimestamp();

        $res = touch($filesystemPath, $created_date);
        if ($res !== true) {
            throw new RuntimeException();
        }
    }

    /**
     * Build temporary table for work, because the query is made on columns that are not indexed
     * and running chunked query on that is very slow.
     */
    private function prepareTemporaryTable()
    {
        $sql = "
          CREATE TEMPORARY TABLE
                `migrate_storage_adapter`
          SELECT
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
                iap_flysystem_path LIKE '{$this->source_adapter}://%'";

        $this->db->query($sql);

        $total = $this->db->getOne('SELECT COUNT(*) FROM `migrate_storage_adapter`');

        return (int)$total;
    }

    private function getChunk($limit)
    {
        $sql
            = "SELECT
                iaf_id,
                iaf_filename,
                iap_flysystem_path,
                iat_iss_id,
                iat_created_date
            FROM
                `migrate_storage_adapter`
            ORDER BY
                iaf_id ASC
            LIMIT {$limit}";

        return $this->db->getAll($sql);
    }

    private function moveFileDatabase($iaf_id, $path)
    {
        $sql = 'UPDATE `issue_attachment_file_path` SET iap_flysystem_path = ? WHERE iap_iaf_id = ?';
        $this->db->query($sql, [$path, $iaf_id]);
        $this->db->query('DELETE FROM `migrate_storage_adapter` WHERE iaf_id=?', [$iaf_id]);
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
            $this->writeln("<error>$message</error>");
        }

        if ($this->source_adapter === 'pdo') {
            $message = "You might need to run 'OPTIMIZE TABLE attachment_chunk' " .
                'to reclaim space from the database';
            $this->writeln("<error>$message</error>");
        }
    }
}

$app = new Silly\Application();
$app->command(Command::USAGE, [new Command(), 'execute']);
$app->setDefaultCommand(Command::DEFAULT_COMMAND, true);
$app->run();
