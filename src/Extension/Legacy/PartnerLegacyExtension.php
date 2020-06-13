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

use Eventum\Event\EventContext;
use Eventum\Event\SystemEvents;
use Eventum\Extension\Provider;
use Partner;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Extension that adds integration of legacy Partner classes to Extension events
 */
class PartnerLegacyExtension implements Provider\SubscriberProvider, EventSubscriberInterface
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
            /** @see PartnerLegacyExtension::handleNewNote */
            SystemEvents::NOTE_CREATED => 'handleNewNote',
            /** @see PartnerLegacyExtension::handleIssueUpdated */
            SystemEvents::ISSUE_UPDATED => 'handleIssueUpdated',
            /** @see PartnerLegacyExtension::handleNewEmail */
            SystemEvents::MAIL_CREATED => 'handleNewEmail',
        ];
    }

    /**
     * @see Workflow::handleNewNote
     */
    public function handleNewNote(EventContext $event): void
    {
        $note_id = $event['note_id'];

        Partner::handleNewNote($event->getIssueId(), $note_id);
    }

    /**
     * @see Workflow::handleIssueUpdated
     */
    public function handleIssueUpdated(EventContext $event): void
    {
        $old_details = $event['old_details'];
        $raw_post = $event['raw_post'];
        Partner::handleIssueChange($event->getIssueId(), $event->getUserId(), $old_details, $raw_post);
    }

    /**
     * @see Workflow::handleNewEmail
     */
    public function handleNewEmail(EventContext $event): void
    {
        $sup_id = $event['sup_id'];

        Partner::handleNewEmail($event->getIssueId(), $sup_id);
    }
}
