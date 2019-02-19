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

class EventumIrcExtension extends AbstractMigration
{
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
        $setup = Setup::get();

        if ($setup['irc_notification'] === 'enabled') {
            $rf = new ReflectionClass(IrcNotifyExtension::class);
            $setup['extensions'][$rf->getName()] = $rf->getFileName();
            Setup::save();
        }
    }

    private function unregisterExtension(): void
    {
        $setup = Setup::get();

        if ($setup['irc_notification'] === 'enabled') {
            $rf = new ReflectionClass(IrcNotifyExtension::class);
            unset($setup['extensions'][$rf->getName()]);
            Setup::save();
        }
    }
}
