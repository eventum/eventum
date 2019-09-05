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

namespace Eventum\Config;

use Eventum\Opcache;
use InvalidArgumentException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\VarExporter\VarExporter;

class ConfigPersistence
{
    public const PREFIX = "<?php\nreturn ";
    public const SUFFIX = ";\n";

    /**
     * Load config from $path.
     * Config file should return configuration array.
     *
     * @param string $path
     * @return array
     */
    public function load(string $path): array
    {
        // return empty array if the file is empty
        // this is to help eventum installation wizard to proceed
        if (!file_exists($path) || !filesize($path)) {
            return [];
        }

        /** @noinspection PhpIncludeInspection */
        $config = require $path;

        if (!is_array($config)) {
            throw new InvalidArgumentException(
                sprintf('The file "%s" did not return a valid PHP array when included', $path)
            );
        }

        return $config;
    }

    /**
     * @param string $path
     * @param array $config
     */
    public function store($path, array $config): void
    {
        $fs = new Filesystem();
        $fs->dumpFile($path, $this->serialize($config));
        Opcache::invalidate($path);
    }

    /**
     * Export config in a format to be stored to config file
     */
    private function serialize(array $config): string
    {
        return self::PREFIX . VarExporter::export($config) . self::SUFFIX;
    }
}
