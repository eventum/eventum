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

namespace Eventum\ServiceProvider;

use Eventum\Console\Command;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class ConsoleCommandsService implements ServiceProviderInterface
{
    public function register(Container $app): void
    {
        $app[Command\ReminderCheckCommand::class] = static function () {
            return new Command\ReminderCheckCommand();
        };
        $app[Command\ExportIssuesCommand::class] = static function () {
            return new Command\ExportIssuesCommand();
        };
        $app[Command\ExtensionEnableCommand::class] = static function () {
            return new Command\ExtensionEnableCommand();
        };
        $app[Command\LdapSyncCommand::class] = static function () {
            return new Command\LdapSyncCommand();
        };
        $app[Command\AttachmentMigrateCommand::class] = static function () {
            return new Command\AttachmentMigrateCommand();
        };
        $app[Command\MonitorCommand::class] = static function () {
            return new Command\MonitorCommand();
        };
        $app[Command\MailRouteCommand::class] = static function () {
            return new Command\MailRouteCommand();
        };
        $app[Command\MailQueueProcessCommand::class] = static function () {
            return new Command\MailQueueProcessCommand();
        };
        $app[Command\MailQueueTruncateCommand::class] = static function () {
            return new Command\MailQueueTruncateCommand();
        };
        $app[Command\MailDownloadCommand::class] = static function () {
            return new Command\MailDownloadCommand();
        };
    }
}
