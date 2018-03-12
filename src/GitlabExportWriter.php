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

namespace Eventum;

class GitlabExportWriter
{
    /**
     * https://gitlab.com/help/user/project/settings/import_export.md#version-history
     */
    const EXPORT_VERSION = '0.2.2';

    /** @var string */
    private $export_version;

    public function __construct($export_version = self::EXPORT_VERSION)
    {
        $this->export_version = $export_version;
    }

    public function export($path)
    {
        $this->writeVersion($path);
    }

    private function writeVersion($path)
    {
        file_put_contents($path . '/VERSION', $this->export_version);
    }
}
