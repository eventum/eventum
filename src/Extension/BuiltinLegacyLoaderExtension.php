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

use AppendIterator;
use CRM;
use Custom_Field;
use DirectoryIterator;
use Partner;
use Workflow;

/**
 * Extension providing autoloader for legacy backends locations:
 * - workflow
 * - partner
 * - custom_field
 * - customer
 */
class BuiltinLegacyLoaderExtension extends AbstractExtension
{
    public function registerAutoloader($loader)
    {
        $classmap = [];

        $el = Partner::getExtensionLoader();
        $classmap += $this->createMap($el);

        $el = Workflow::getExtensionLoader();
        $classmap += $this->createMap($el);

        $el = Custom_Field::getExtensionLoader();
        $classmap += $this->createMap($el);

        $el = CRM::getExtensionLoader();
        $classmap += $this->createMap($el);

        $loader->addClassMap($classmap);
    }

    /**
     * @param ExtensionLoader $loader
     * @return array
     */
    private function createMap($loader)
    {
        $map = [];

        $di = new AppendIterator();
        foreach ($loader->getPaths() as $path) {
            if (!is_dir($path)) {
                continue;
            }
            $di->append(new DirectoryIterator($path));
        }

        // iterate over dirs, and accept only items that ExtensionLoader provided
        $files = $loader->getFileList();
        foreach ($di as $fi) {
            $filename = $fi->getFilename();
            if (!isset($files[$filename])) {
                continue;
            }

            $classname = $loader->getClassName($filename);
            $map[$classname] = $fi->getPathname();
        }

        return $map;
    }
}
