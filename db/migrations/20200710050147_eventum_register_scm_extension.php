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

use Eventum\Db\AbstractMigration;
use Eventum\Extension\RegisterExtension;
use Eventum\Extension\ScmExtension;
use Eventum\ServiceContainer;

class EventumRegisterScmExtension extends AbstractMigration
{
    private const EXTENSIONS = [
        ScmExtension::class,
    ];

    public function up(): void
    {
        $this->registerExtensions();
    }

    public function down(): void
    {
        $this->unregisterExtensions();
    }

    private function registerExtensions(): void
    {
        if ($this->isEnabled()) {
            $register = new RegisterExtension();
            $register->register(...self::EXTENSIONS);
        }
    }

    private function unregisterExtensions(): void
    {
        if ($this->isEnabled()) {
            $register = new RegisterExtension();
            $register->unregister(...self::EXTENSIONS);
        }
    }

    private function isEnabled(): bool
    {
        return ServiceContainer::getConfig()['scm_integration'] === 'enabled';
    }
}
