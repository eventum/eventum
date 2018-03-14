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
    public function up()
    {
        $files = $this->getFiles();

        $total = count($files);
        $progressBar = $this->createProgressBar($total);
        $progressBar->start();
        $progressBar->setRedrawFrequency($total / 10);
        $updated = 0;

        foreach ($files as $iaf_id => $fileName) {
            $normalizedName = Normalizer::normalize($fileName);
            if ($fileName !== $normalizedName) {
                $this->setFilename($iaf_id, $normalizedName);
                $updated++;
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->writeln('');
        $this->writeln("Updated $updated filenames out of $total");
    }

    private function getFiles()
    {
        $sql = "SELECT iaf_id, iaf_filename FROM `issue_attachment_file` WHERE iaf_filename!=''";

        return $this->queryPair($sql, 'iaf_id', 'iaf_filename');
    }

    private function setFilename($iaf_id, $fileName)
    {
        $fileName = $this->quote($fileName);
        $sql = "UPDATE `issue_attachment_file` SET  iaf_filename=$fileName where iaf_id=$iaf_id";

        $this->query($sql);
    }
}
