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

use Date_Helper;
use DB_Helper;
use Eventum\Db\DatabaseException;
use Eventum\Event\ResultableEvent;
use Eventum\Event\SystemEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AuditTrailExtension implements Provider\SubscriberProvider, EventSubscriberInterface
{
    public function getSubscribers(): array
    {
        return [
            self::class,
        ];
    }

    public static function getSubscribedEvents(): array
    {
        return [
            /** @see AuditTrailExtension::canAccessIssue */
            // This should be with lowest priority to log final result
            SystemEvents::ACCESS_ISSUE => ['canAccessIssue', PHP_INT_MIN],
        ];
    }

    /**
     * @see Workflow::canAccessIssue
     */
    public function canAccessIssue(ResultableEvent $event): void
    {
        if (!$event['internal']) {
            $this->log($event->getResult(), $event['issue_id'], $event['usr_id'], $_SERVER['REQUEST_URI'] ?? '');
        }
    }

    private function log(bool $return, int $issue_id, int $usr_id, string $url): void
    {
        [$item, $item_id] = $this->extractInfoFromURL($url);

        $sql = 'INSERT INTO
                    `issue_access_log`
                SET
                    alg_iss_id = ?,
                    alg_usr_id = ?,
                    alg_created = ?,
                    alg_ip_address = ?,
                    alg_failed = ?,
                    alg_item = ?,
                    alg_item_id = ?,
                    alg_url = ?';
        $params = [
            $issue_id,
            $usr_id,
            Date_Helper::getCurrentDateGMT(),
            $_SERVER['REMOTE_ADDR'] ?? null,
            (int)!$return,
            $item,
            $item_id,
            $url,
        ];
        try {
            DB_Helper::getInstance()->query($sql, $params);
        } catch (DatabaseException $e) {
            // do nothing besides log it
        }
    }

    private function extractInfoFromURL(string $url)
    {
        if (preg_match("/view_note\.php\?id=(?P<item_id>\d+)/", $url, $matches)) {
            return ['note', $matches[1]];
        }

        if (preg_match("/view_email\.php\?ema_id=\d+&id=(?P<item_id>\d+)/", $url, $matches)) {
            return ['email', $matches[1]];
        }

        if (preg_match("/download\.php\?cat=attachment&id=(?P<item_id>\d+)/", $url, $matches)) {
            return ['file', $matches[1]];
        }

        if (preg_match("/update\.php/", $url, $matches)) {
            return ['update', null];
        }

        return [null, null];
    }
}
