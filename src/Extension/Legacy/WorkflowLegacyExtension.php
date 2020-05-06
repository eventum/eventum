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

namespace Eventum\Extension\Legacy;

use Eventum\Event\ResultableEvent;
use Eventum\Event\SystemEvents;
use Eventum\Extension\Provider;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Workflow;

/**
 * Extension that adds integration of legacy workflow classes to Extension events
 */
class WorkflowLegacyExtension implements Provider\SubscriberProvider, EventSubscriberInterface
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
            /** @see WorkflowLegacyExtension::canAccessIssue */
            SystemEvents::ACCESS_ISSUE => 'canAccessIssue',
        ];
    }

    /**
     * @see Workflow::canAccessIssue
     */
    public function canAccessIssue(ResultableEvent $event): void
    {
        if (!$backend = Workflow::getBackend($event['prj_id'])) {
            return;
        }

        $result = $backend->canAccessIssue($event['prj_id'], $event['issue_id'], $event['usr_id']);
        if ($result !== null) {
            $event->setResult($result);
        }
    }
}
