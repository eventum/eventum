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

namespace Eventum\Extension;

use Misc;

class ExtensionLoader
{
    /**
     * Get Filename -> Classname of extensions found
     *
     * @return array
     */
    public function getFileList($paths)
    {
        $list = $files = [];
        foreach ($paths as $path) {
            $files = array_merge($files, Misc::getFileList($path));
        }

        foreach ($files as $file) {
            $fileName = basename($file);

            // make sure we only list the backends
            if (!preg_match('/^class\.(.+)\.php$/', $file)) {
                continue;
            }

            $list[$fileName] = $this->getDisplayName($fileName);
        }

        return $list;
    }

    /**
     * Returns the 'pretty' name of the backend
     *
     * @param string $fileName
     * @return string
     */
    private function getDisplayName($fileName)
    {
        return ucwords(str_replace('_', ' ', $fileName));
    }
}
