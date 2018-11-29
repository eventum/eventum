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

use Eventum\Event\Subscriber\IrcSubscriber;
use Setup;

class IrcNotifyExtension extends AbstractExtension
{
    /**
     * {@inheritdoc}
     */
    public function getSubscribers()
    {
        $subscribers = [];

        $setup = Setup::get();
        if ($setup['irc_notification'] === 'enabled') {
            $subscribers[] = IrcSubscriber::class;
        }

        return $subscribers;
    }
}
