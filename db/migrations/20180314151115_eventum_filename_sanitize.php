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

use Eventum\Db\AbstractMigration;
use glen\FilenameNormalizer\Normalizer;

class EventumFilenameSanitize extends AbstractMigration
{
    public function up(): void
    {
        $files = $this->getFiles();
        $total = count($files);
        $updated = 0;

        foreach ($this->getIterator($files, $total) as $iaf_id => $fileName) {
            $normalizedName = Normalizer::normalize($fileName);
            if ($fileName !== $normalizedName) {
                $this->setFilename($iaf_id, $normalizedName);
                $updated++;
            }
        }

        $this->writeln('');
        $this->writeln("Updated $updated filenames out of $total");
    }

    private function getIterator($entries, $total)
    {
        if (!$total) {
            return;
        }

        $progressBar = $this->createProgressBar($total);
        $progressBar->start();
        foreach ($entries as $entry) {
            yield $entry;
            $progressBar->advance();
        }
        $progressBar->finish();
        $this->writeln('');
    }

    private function getFiles(): array
    {
        $sql = "SELECT iaf_id, iaf_filename FROM `issue_attachment_file` WHERE iaf_filename!=''";

        return $this->queryPair($sql, 'iaf_id', 'iaf_filename');
    }

    private function setFilename($iaf_id, $fileName): void
    {
        $fileName = $this->quote($fileName);
        $sql = "UPDATE `issue_attachment_file` SET  iaf_filename=$fileName where iaf_id=$iaf_id";

        $this->query($sql);
    }
}
