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

use Date_Helper;
use DB_Helper;
use Issue;
use Setup;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Workflow;

class IrcSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            SystemEvents::IRC_NOTIFY => 'notifyIRC',
        ];
    }

    /**
     * Save event details to irc_notice table.
     *
     * @param GenericEvent $event
     */
    public function notifyIRC(GenericEvent $event)
    {
        if (!$this->notificationEnabled()) {
            return;
        }

        $category = $event['category'];
        $notice = Workflow::formatIRCMessage(
            $event['prj_id'], $event['notice'], $event['issue_id'],
            $event['usr_id'], $category, $event['type']
        );
        // assign back in case workflow modified value
        $event['category'] = $category;

        if ($notice === false) {
            return;
        }

        $params = [
            'ino_prj_id' => $event['prj_id'],
            'ino_created_date' => Date_Helper::getCurrentDateGMT(),
            'ino_status' => 'pending',
            'ino_message' => $event['notice'],
            'ino_category' => $category,
        ];

        if ($event['issue_id']) {
            $params['ino_iss_id'] = $event['issue_id'];
        }
        if ($event['usr_id']) {
            $params['ino_target_usr_id'] = $event['usr_id'];
        }

        $stmt = 'INSERT INTO `irc_notice` SET ' . DB_Helper::buildSet($params);
        DB_Helper::getInstance()->query($stmt, $params);
    }

    private function notificationEnabled()
    {
        $setup = Setup::get();

        return $setup['irc_notification'] === 'enabled';
    }
}
