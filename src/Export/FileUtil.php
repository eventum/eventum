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
