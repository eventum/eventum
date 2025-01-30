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

use RuntimeException;

final class FileUtil
{
    public static function readJsonFile(string $fileName): array
    {
        $contents = file_get_contents($fileName);
        if (!$contents) {
            throw new RuntimeException("Could not read {$fileName}");
        }
        $result = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

        return $result;
    }

    public static function writeFile(string $fileName, string $content): void
    {
        self::ensureDirectory(dirname($fileName));
        $result = file_put_contents($fileName, $content);

        if ($result === false) {
            throw new RuntimeException("Could not write {$fileName}");
        }
    }

    public static function createWritableStream(string $fileName)
    {
        $directory = dirname($fileName);
        self::ensureDirectory($directory);
        $stream = fopen($fileName, 'wb');
        if (!$stream) {
            throw new RuntimeException("Can't open {$fileName} for writing");
        }

        return $stream;
    }

    public static function ensureDirectory(string $directory): void
    {
        if (is_dir($directory)) {
            return;
        }

        if (!mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $directory));
        }
    }
}
