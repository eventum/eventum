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
use Eventum\Extension\IrcNotifyExtension;
use Eventum\Extension\RegisterExtension;
use Eventum\ServiceContainer;

class EventumIrcExtension extends AbstractMigration
{
    private const EXTENSION = IrcNotifyExtension::class;

    public function up(): void
    {
        $this->registerExtension();
    }

    public function down(): void
    {
        $this->unregisterExtension();
    }

    private function registerExtension(): void
    {
        if ($this->isEnabled()) {
            $register = new RegisterExtension();
            $register->register(self::EXTENSION);
        }
    }

    private function unregisterExtension(): void
    {
        if ($this->isEnabled()) {
            $register = new RegisterExtension();
            $register->unregister(self::EXTENSION);
        }
    }

    private function isEnabled(): bool
    {
        return ServiceContainer::getConfig()['irc_notification'] === 'enabled';
    }
}
