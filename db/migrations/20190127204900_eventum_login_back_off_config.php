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
use Eventum\ServiceContainer;

class EventumLoginBackOffConfig extends AbstractMigration
{
    public function up(): void
    {
        $config = ServiceContainer::getConfig()['auth'];
        $config['login_backoff'] = [
            'count' => $this->getBackoffCount(),
            'minutes' => $this->getBackoffMinutes(),
        ];
        Setup::save();
    }

    /**
     * Get number of failed attempts before Back-Off locking kicks in.
     * If set to null do not use Back-Off locking.
     */
    private function getBackoffCount(): ?int
    {
        if (!defined('APP_FAILED_LOGIN_BACKOFF_COUNT')) {
            return null;
        }

        $count = APP_FAILED_LOGIN_BACKOFF_COUNT;
        if ($count === false) {
            return null;
        }

        return $count;
    }

    /**
     * How many minutes to lock account for during Back-Off
     */
    private function getBackoffMinutes(): ?int
    {
        if (!defined('APP_FAILED_LOGIN_BACKOFF_MINUTES')) {
            return 15;
        }

        return APP_FAILED_LOGIN_BACKOFF_MINUTES;
    }

    public function down(): void
    {
    }
}
