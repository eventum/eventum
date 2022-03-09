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

namespace Eventum\Export;

class GitlabExportWriter
{
    /**
     * https://docs.gitlab.com/ee/user/project/settings/import_export.html#version-history
     */
    private const EXPORT_VERSION = '0.2.2';

    /** @var string */
    private $exportVersion;
    /** @var string */
    private $directory;

    public function __construct(string $directory, string $exportVersion = self::EXPORT_VERSION)
    {
        $this->directory = $directory;
        $this->exportVersion = $exportVersion;
    }

    public function export(): void
    {
        $this->writeVersion();
    }

    private function writeVersion(): void
    {
        $this->writeFile('VERSION', $this->exportVersion);
    }

    private function writeFile(string $fileName, string $content): void
    {
        FileUtil::writeFile($this->directory . '/' . $fileName, $content);
    }
}
