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

    public function __construct(string $exportVersion = self::EXPORT_VERSION)
    {
        $this->exportVersion = $exportVersion;
    }

    public function export($path): void
    {
        $this->writeVersion($path);
    }

    private function writeVersion($path): void
    {
        file_put_contents($path . '/VERSION', $this->exportVersion);
    }
}
