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

namespace Eventum\Test\Traits;

trait DataFileTrait
{
    protected function getDataFile(string $fileName): string
    {
        $dataFile = dirname(__DIR__) . '/data/' . $fileName;
        $this->assertFileExists($dataFile);

        return $dataFile;
    }

    /**
     * Read file from tests/data directory.
     *
     * @param string $filename
     * @return string
     */
    protected function readDataFile(string $filename): string
    {
        return $this->readFile($this->getDataFile($filename));
    }

    /**
     * @param string $filename
     * @return string
     */
    protected function readFile(string $filename): string
    {
        $this->assertFileExists($filename);
        $content = file_get_contents($filename);
        $this->assertNotEmpty($content);

        return $content;
    }
}
