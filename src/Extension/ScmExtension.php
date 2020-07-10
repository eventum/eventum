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

use Eventum\Extension\Provider\ExtensionProvider;
use Eventum\Logger\LoggerTrait;
use Eventum\ServiceContainer;

class ScmExtension implements ExtensionProvider
{
    use LoggerTrait;

    /** @var bool */
    private $enabled;

    public function __construct()
    {
        $config = ServiceContainer::getConfig();
        $this->enabled = $config['scm_integration'] === 'enabled';
    }
}
