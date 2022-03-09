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

use DateTime;
use Eventum\Config\Paths;
use Eventum\Export\ValueConverter\DateTimeValueConverter;

class GitlabExportWriter
{
    /**
     * https://docs.gitlab.com/ee/user/project/settings/import_export.html#version-history
     */
    private const EXPORT_VERSION = '0.2.4';

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
        $this->writeProject();
        $this->writeProjectFeature();
    }

    private function writeVersion(): void
    {
        $this->writeFile('VERSION', $this->exportVersion);
    }

    private function writeProject(): void
    {
        $data = $this->readJsonFile('project.json');
        $data['description'] = 'Issues exported from Eventum';
        $this->writeJsonFile('tree/project.json', $data);
    }

    private function writeProjectFeature(): void
    {
        $dateTimeConverter = new DateTimeValueConverter();
        $createdAt = $dateTimeConverter->convert(new DateTime());
        $data = $this->readJsonFile('project_feature.json');
        $data['created_at'] = $createdAt;
        $data['updated_at'] = $createdAt;

        $this->writeJsonFile('tree/project/project_feature.ndjson', $data);
    }

    private function writeFile(string $fileName, string $content): void
    {
        FileUtil::writeFile($this->directory . '/' . $fileName, $content);
    }

    private function readJsonFile(string $fileName): array
    {
        return FileUtil::readJsonFile(Paths::APP_RESOURCES_PATH . '/export/gitlab/' . $fileName);
    }

    private function writeJsonFile(string $fileName, array $data): void
    {
        $this->writeFile($fileName, json_encode($data, JSON_THROW_ON_ERROR));
    }
}
