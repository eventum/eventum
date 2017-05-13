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

use CRM;
use Custom_Field;
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

        // iterate over list and fill with absolute path
        $files = $loader->getFileList();
        foreach ($files as $filename => $description) {
            $classname = $loader->getClassName($filename);
            $map[$classname] = $loader->findClassFilename($filename);
        }

        return $map;
    }
}
