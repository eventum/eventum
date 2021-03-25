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

namespace Eventum\Test;

use Eventum\Extension\ExtensionManager;
use Eventum\Test\Traits\DataFileTrait;

class TestCase extends \PHPUnit\Framework\TestCase
{
    use DataFileTrait;

    /**
     * Create ExtensionManager with given config
     *
     * @return ExtensionManager
     */
    protected function getExtensionManager(array $config): ExtensionManager
    {
        $em = new ExtensionManager($config);
        $em->boot();

        return $em;
    }
}
