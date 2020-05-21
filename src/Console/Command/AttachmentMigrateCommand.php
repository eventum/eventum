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

namespace Eventum\Console\Command;

use DateTime;
use DateTimeZone;
use DB_Helper;
use Eventum\Attachment\AttachmentManager;
use Eventum\Attachment\StorageManager;
use Eventum\Console\ConsoleTrait;
use Eventum\Db\Adapter\AdapterInterface;
use Exception;
use League\Flysystem\Adapter\Local;
use League\Flysystem\File;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;
use Misc;
use RuntimeException;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AttachmentMigrateCommand extends SymfonyCommand
{
    use ConsoleTrait;

    public const DEFAULT_COMMAND = 'attachment:migrate';
    public const USAGE = self::DEFAULT_COMMAND . ' [source_adapter] [target_adapter] [--chunksize=] [--limit=] [--migrate] [--verify]';
    private const DEFAULT_CHUNKSIZE = 100;

    protected static $defaultName = 'eventum:' . self::DEFAULT_COMMAND;

    /** @var AdapterInterface */
    private $db;

    /** @var StorageManager */
    private $sm;

    /** @var string */
    private $source_adapter;

    /** @var string */
    private $target_adapter;

    protected function configure(): void
    {
        $this
            ->addArgument('source_adapter', InputArgument::REQUIRED)
            ->addArgument('target_adapter', InputArgument::REQUIRED)
            ->addOption('chunksize', null, InputOption::VALUE_REQUIRED)
            ->addOption('migrate', null, InputOption::VALUE_NONE)
            ->addOption('verify', null, InputOption::VALUE_NONE)
            ->addOption('limit', 'l', InputOption::VALUE_REQUIRED);
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $source_adapter = $input->getArgument('source_adapter');
        $target_adapter = $input->getArgument('target_adapter');
        $migrate = $input->getOption('migrate');
        $verify = $input->getOption('verify');
        $limit = $input->getOption('limit');
        $chunksize = $input->getOption('chunksize');

        $this($output, $source_adapter, $target_adapter, $migrate, $verify, $limit, $chunksize);

        return 0;
    }

    public function __invoke(OutputInterface $output, $source_adapter, $target_adapter, $migrate, $verify, $limit, $chunksize): void
    {
        $chunksize = $chunksize ?: self::DEFAULT_CHUNKSIZE;
        $this->output = $output;
        $this->assertInput($source_adapter, $target_adapter, $migrate, $verify);

        $this->source_adapter = $source_adapter;
        $this->target_adapter = $target_adapter;

        $this->db = DB_Helper::getInstance();
        $this->sm = StorageManager::get();
        if ($verify) {
            $this->verifyAttachments((int)$chunksize, (int)$limit);
        } else {
            $this->migrateAttachments((int)$chunksize, (int)$limit);
            $this->postUpgradeNotice();
        }
    }

    private function verifyAttachments($chunkSize, $limit): void
    {
        $this->writeln("Verifying data in '{$this->source_adapter}://' Adapter");
        $this->writeln('Preparing temporary table. Please wait...');
        $total = $this->prepareTemporaryTable($limit);
        $this->writeln("Verifying $total file(s)");

        if (!$total) {
            $this->writeln('Nothing to verify');

            return;
        }

        $totalSize = 0;
        foreach ($this->getIterator($total, $chunkSize) as $entry) {
            try {
                $file = $this->sm->getFile($entry['iap_flysystem_path']);

                $fileSize = $file->getSize();
                if ($fileSize === false) {
                    throw new RuntimeException("Failed to obtain size of {$entry['iap_flysystem_path']}");
                }

                $filePath = $this->getLocalPath($file);
                if ($filePath) {
                    if (!file_exists($filePath)) {
                        throw new RuntimeException("File does not exist: {$filePath}");
                    }

                    if (filesize($filePath) !== $fileSize) {
                        throw new RuntimeException("File size mismatch: {$filePath}: {$fileSize}");
                    }
                }

                $totalSize += $fileSize;
            } catch (FileNotFoundException $e) {
                $this->writeln("<error>ERROR</error>: {$e->getMessage()}");
                continue;
            } catch (Exception $e) {
                $this->writeln("<error>ERROR</error>: {$e->getMessage()}");
                continue;
            }
        }
        $formatted = Misc::formatFileSize($totalSize);
        $this->writeln("Scanned $formatted files");
    }

    private function migrateAttachments($chunkSize, $limit): void
    {
        $this->writeln("Migrating data from '{$this->source_adapter}://' to '{$this->target_adapter}://'");
        $this->writeln('Preparing temporary table. Please wait...');
        $total = $this->prepareTemporaryTable($limit);
        $this->writeln("Moving $total file(s)");

        if (!$total) {
            $this->writeln('Nothing to migrate');

            return;
        }

        foreach ($this->getIterator($total, $chunkSize) as $file) {
            $this->moveFile($file);
        }
    }

    private function moveFile($file): void
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
     * @param array $entry
     */
    private function touchLocalFile($path, array $entry): void
    {
        $file = $this->sm->getFile($path);
        $filesystemPath = $this->getLocalPath($file);
        if (!$filesystemPath) {
            return;
        }

        $date = new DateTime($entry['iat_created_date'], new DateTimeZone('UTC'));
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
    private function prepareTemporaryTable($limit)
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

        if ($limit) {
            $sql .= " LIMIT $limit";
        }

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

    private function moveFileDatabase($iaf_id, $path): void
    {
        $sql = 'UPDATE `issue_attachment_file_path` SET iap_flysystem_path = ? WHERE iap_iaf_id = ?';
        $this->db->query($sql, [$path, $iaf_id]);
        $this->db->query('DELETE FROM `migrate_storage_adapter` WHERE iaf_id=?', [$iaf_id]);
    }

    private function assertInput($source_adapter, $target_adapter, $migrate, $verify): void
    {
        if (!$migrate && !$verify) {
            throw new RuntimeException(
                'WARNING: Migrating data has risks. ' .
                "Make sure all your data is backed up before continuing.\n" .

                "Pass '--migrate' argument to skip this warning " .
                'and perform the migration.'
            );
        }

        if ($migrate && !$source_adapter) {
            throw new RuntimeException('Verify: Must specify source adapters');
        }

        if (!$source_adapter || (!$target_adapter && !$verify)) {
            throw new RuntimeException('Migrate: Must specify source and target adapters');
        }
    }

    private function postUpgradeNotice(): void
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

    private function getIterator($total, $chunkSize)
    {
        $formatName = 'debug';
        $progressBar = $this->createProgressBar($total, $formatName);
        $progressBar->start();

        for ($i = 0, $nchunks = ceil($total / $chunkSize); $i < $nchunks; $i++) {
            $files = $this->getChunk($chunkSize);
            if (empty($files)) {
                break;
            }

            foreach ($files as $file) {
                $progressBar->setMessage($file['iaf_id'], 'id');
                $progressBar->setMessage($file['iap_flysystem_path'], 'filename');

                yield $file;
                $progressBar->advance();
            }
        }

        $progressBar->setFormat($formatName);
        $progressBar->finish();
        $this->writeln('');
    }

    private function createProgressBar($total, $formatName)
    {
        $format = ProgressBar::getFormatDefinition($formatName);
        $format .= ' (%id%: %filename%)';
        ProgressBar::setFormatDefinition('custom', $format);

        $progressBar = new ProgressBar($this->output, $total);
        $progressBar->setFormat('custom');

        // avoid displaying placeholders
        $progressBar->setMessage('', 'id');
        $progressBar->setMessage('', 'filename');

        return $progressBar;
    }

    /**
     * @param File $file
     * @return null|string
     */
    private function getLocalPath(File $file)
    {
        /** @var Filesystem $fs */
        $fs = $file->getFilesystem();
        $adapter = $fs->getAdapter();
        if (!$adapter instanceof Local) {
            return null;
        }

        return $adapter->applyPathPrefix($file->getPath());
    }
}
