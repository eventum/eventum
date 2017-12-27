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

namespace Eventum\Event;

use Misc;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

class HistorySubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            SystemEvents::HISTORY_ADD => 'historyAdded',
        ];
    }

    /**
     * @param GenericEvent $event
     */
    public function historyAdded(GenericEvent $event)
    {
        $his_summary = Misc::processTokens(ev_gettext($event['his_summary']), $event['his_context']);

        error_log("HISTORY[issue {$event['his_iss_id']}]: {$event['his_id']}: $his_summary");
    }
}
