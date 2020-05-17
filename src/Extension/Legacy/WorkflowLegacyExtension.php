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
            /** @see WorkflowLegacyExtension::handlePriorityChange */
            SystemEvents::ISSUE_UPDATED_PRIORITY => 'handlePriorityChange',
            /** @see WorkflowLegacyExtension::handleSeverityChange */
            SystemEvents::ISSUE_UPDATED_SEVERITY => 'handleSeverityChange',
            /** @see WorkflowLegacyExtension::handleBlockedEmail */
            SystemEvents::EMAIL_BLOCKED => 'handleBlockedEmail',
            /** @see WorkflowLegacyExtension::handleAssignmentChange */
            SystemEvents::ISSUE_ASSIGNMENT_CHANGE => 'handleAssignmentChange',
            /** @see WorkflowLegacyExtension::handleNewIssue */
            SystemEvents::ISSUE_CREATED => 'handleNewIssue',
            /** @see WorkflowLegacyExtension::handleManualEmailAssociation */
            SystemEvents::MAIL_ASSOCIATED_MANUAL => 'handleManualEmailAssociation',
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
     * @see Workflow::handlePriorityChange
     */
    public function handlePriorityChange(EventContext $event): void
    {
        if (!$backend = $this->getBackend($event)) {
            return;
        }

        $old_details = $event['old_details'];
        $changes = $event['changes'];
        $backend->handlePriorityChange($event->getProjectId(), $event->getIssueId(), $event->getUserId(), $old_details, $changes);
    }

    /**
     * @see Workflow::handleSeverityChange
     */
    public function handleSeverityChange(EventContext $event): void
    {
        if (!$backend = $this->getBackend($event)) {
            return;
        }

        $old_details = $event['old_details'];
        $changes = $event['changes'];
        $backend->handleSeverityChange($event->getProjectId(), $event->getIssueId(), $event->getUserId(), $old_details, $changes);
    }

    /**
     * @see Workflow::handleBlockedEmail
     */
    public function handleBlockedEmail(EventContext $event): void
    {
        if (!$backend = $this->getBackend($event)) {
            return;
        }

        $email_details = $event['email_details'];
        $type = $event['type'];
        $backend->handleBlockedEmail($event->getProjectId(), $event->getIssueId(), $email_details, $type);
    }

    /**
     * @see Workflow::handleAssignmentChange
     */
    public function handleAssignmentChange(EventContext $event): void
    {
        if (!$backend = $this->getBackend($event)) {
            return;
        }

        $issue_details = $event['issue_details'];
        $new_assignees = $event['new_assignees'];
        $remote_assignment = $event['remote_assignment'];

        $backend->handleAssignmentChange($event->getProjectId(), $event->getIssueId(), $event->getUserId(), $issue_details, $new_assignees, $remote_assignment);
    }

    /**
     * @see Workflow::handleNewIssue
     */
    public function handleNewIssue(EventContext $event): void
    {
        if (!$backend = $this->getBackend($event)) {
            return;
        }

        $has_TAM = $event['has_TAM'];
        $has_RR = $event['has_RR'];
        $backend->handleNewIssue($event->getProjectId(), $event->getIssueId(), $has_TAM, $has_RR);
    }

    /**
     * @see Workflow::handleManualEmailAssociation
     */
    public function handleManualEmailAssociation(EventContext $event): void
    {
        if (!$backend = $this->getBackend($event)) {
            return;
        }

        $backend->handleManualEmailAssociation($event->getProjectId(), $event->getIssueId());
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
