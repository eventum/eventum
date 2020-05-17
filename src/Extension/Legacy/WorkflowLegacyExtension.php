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

use Abstract_Workflow_Backend;
use Eventum\Attachment\AttachmentGroup;
use Eventum\Event\EventContext;
use Eventum\Event\ResultableEvent;
use Eventum\Event\SystemEvents;
use Eventum\Extension\Provider;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
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
            /** @see WorkflowLegacyExtension::handleIssueUpdated */
            SystemEvents::ISSUE_UPDATED => 'handleIssueUpdated',
            /** @see WorkflowLegacyExtension::preIssueUpdated */
            SystemEvents::ISSUE_UPDATED_BEFORE => 'preIssueUpdated',
            /** @see WorkflowLegacyExtension::handleAttachment */
            SystemEvents::ATTACHMENT_ATTACHMENT_GROUP => 'handleAttachment',
            /** @see WorkflowLegacyExtension::shouldAttachFile */
            SystemEvents::ATTACHMENT_ATTACH_FILE => 'shouldAttachFile',
            /** @see WorkflowLegacyExtension::canAccessIssue */
            SystemEvents::ACCESS_ISSUE => 'canAccessIssue',
        ];
    }

    /**
     * @see Workflow::handleIssueUpdated
     */
    public function handleIssueUpdated(GenericEvent $event): void
    {
        if (!$backend = $this->getBackend($event)) {
            return;
        }

        $backend->handleIssueUpdated($event['prj_id'], $event['issue_id'], $event['usr_id'], $event['old_details'], $event['raw_post']);
    }

    /**
     * @see Workflow::preIssueUpdated
     */
    public function preIssueUpdated(GenericEvent $event): void
    {
        if (!$backend = $this->getBackend($event)) {
            return;
        }

        $result = $backend->preIssueUpdated($event['prj_id'], $event['issue_id'], $event['usr_id'], $event['changes']);
        if ($result !== true) {
            $event->stopPropagation();
        }
    }

    /**
     * @see Workflow::handleAttachment
     */
    public function handleAttachment(EventContext $event): void
    {
        if (!$backend = $this->getBackend($event)) {
            return;
        }

        /** @var AttachmentGroup $attachmentGroup */
        $attachmentGroup = $event->getSubject();
        $backend->handleAttachment($event->getProjectId(), $event->getIssueId(), $event->getUserId(), $attachmentGroup);
    }

    /**
     * @see Workflow::shouldAttachFile
     */
    public function shouldAttachFile(ResultableEvent $event): void
    {
        if (!$backend = $this->getBackend($event)) {
            return;
        }

        /** @var array $attachment */
        $attachment = $event->getSubject();
        $result = $backend->shouldAttachFile($event->getProjectId(), $event->getIssueId(), $event->getUserId(), $attachment);
        $event->setResult($result);
    }

    /**
     * @see Workflow::canAccessIssue
     */
    public function canAccessIssue(ResultableEvent $event): void
    {
        if (!$backend = $this->getBackend($event)) {
            return;
        }

        $result = $backend->canAccessIssue($event['prj_id'], $event['issue_id'], $event['usr_id']);
        if ($result !== null) {
            $event->setResult($result);
        }
    }

    protected function getBackend(GenericEvent $event): ?Abstract_Workflow_Backend
    {
        return Workflow::getBackend($event['prj_id']);
    }
}
