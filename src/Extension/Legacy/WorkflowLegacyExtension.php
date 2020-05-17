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
use Zend\Mail\Address;

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
            /** @see WorkflowLegacyExtension::handleNewNote */
            SystemEvents::NOTE_CREATED => 'handleNewNote',
            /** @see WorkflowLegacyExtension::getAllowedStatuses */
            SystemEvents::ISSUE_ALLOWED_STATUSES => 'getAllowedStatuses',
            /** @see WorkflowLegacyExtension::handleIssueClosed */
            SystemEvents::ISSUE_CLOSED => 'handleIssueClosed',
            /** @see WorkflowLegacyExtension::handleCustomFieldsUpdated */
            SystemEvents::CUSTOM_FIELDS_UPDATED => 'handleCustomFieldsUpdated',
            /** @see WorkflowLegacyExtension::handleSubscription */
            SystemEvents::NOTIFICATION_HANDLE_SUBSCRIPTION => 'handleSubscription',
            /** @see WorkflowLegacyExtension::shouldEmailAddress */
            SystemEvents::NOTIFICATION_NOTIFY_ADDRESS => 'shouldEmailAddress',
            /** @see WorkflowLegacyExtension::getAdditionalEmailAddresses */
            SystemEvents::NOTIFICATION_NOTIFY_ADDRESSES_EXTRA => 'getAdditionalEmailAddresses',
            /** @see WorkflowLegacyExtension::canEmailIssue */
            SystemEvents::ACCESS_ISSUE_EMAIL => 'canEmailIssue',
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
     * @see Workflow::handleCustomFieldsUpdated
     */
    public function handleCustomFieldsUpdated(EventContext $event): void
    {
        if (!$backend = $this->getBackend($event)) {
            return;
        }

        $old = $event['old'];
        $new = $event['new'];
        $changed = $event['changed'];
        $backend->handleCustomFieldsUpdated($event->getProjectId(), $event->getIssueId(), $old, $new, $changed);
    }

    /**
     * @see Workflow::handleSubscription
     */
    public function handleSubscription(ResultableEvent $event): void
    {
        if (!$backend = $this->getBackend($event)) {
            return;
        }

        $subscriber_usr_id = $event['subscriber_usr_id'];
        $email = $event['email'];
        $actions = $event['actions'];

        $result = $backend->handleSubscription($event->getProjectId(), $event->getIssueId(), $subscriber_usr_id, $email, $actions);

        // assign back, in case these were modified
        $event['subscriber_usr_id'] = $subscriber_usr_id;
        $event['email'] = $email;
        $event['actions'] = $actions;

        $event->setResult($result);
    }

    /**
     * @see Workflow::shouldEmailAddress
     */
    public function shouldEmailAddress(ResultableEvent $event): void
    {
        if (!$backend = $this->getBackend($event)) {
            return;
        }

        $address = $event['address'];
        $type = $event['type'];
        $result = $backend->shouldEmailAddress($event->getProjectId(), $address, $event->getIssueId(), $type);
        if ($result !== null) {
            $event->setResult($result);
        }
    }

    /**
     * @see Workflow::shouldEmailAddress
     */
    public function getAdditionalEmailAddresses(ResultableEvent $event): void
    {
        if (!$backend = $this->getBackend($event)) {
            return;
        }

        $eventName = $event['eventName'];
        $extra = $event['extra'];
        $result = $backend->getAdditionalEmailAddresses($event->getProjectId(), $event->getIssueId(), $eventName, $extra);
        if ($result !== null) {
            $event->setResult($result);
        }
    }

    /**
     * @see Workflow::canEmailIssue
     */
    public function canEmailIssue(ResultableEvent $event): void
    {
        if (!$backend = $this->getBackend($event)) {
            return;
        }

        /** @var Address $address */
        $address = $event->getSubject();
        $email = $address->getEmail();
        $result = $backend->canEmailIssue($event->getProjectId(), $event->getIssueId(), $email);
        if ($result !== null) {
            $event->setResult($result);
        }
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
     * @see Workflow::handleNewNote
     */
    public function handleNewNote(EventContext $event): void
    {
        if (!$backend = $this->getBackend($event)) {
            return;
        }

        $closing = $event['closing'];
        $note_id = $event['note_id'];
        $backend->handleNewNote($event->getProjectId(), $event->getIssueId(), $event->getUserId(), $closing, $note_id);
    }

    /**
     * @see Workflow::getAllowedStatuses
     */
    public function getAllowedStatuses(ResultableEvent $event): void
    {
        if (!$backend = $this->getBackend($event)) {
            return;
        }

        $result = $backend->getAllowedStatuses($event->getProjectId(), $event->getIssueId());
        $event->setResult($result);
    }

    /**
     * @see Workflow::handleIssueClosed
     */
    public function handleIssueClosed(EventContext $event): void
    {
        if (!$backend = $this->getBackend($event)) {
            return;
        }

        $send_notification = $event['send_notification'];
        $resolution_id = $event['resolution_id'];
        $status_id = $event['status_id'];
        $reason = $event['reason'];

        $backend->handleIssueClosed($event->getProjectId(), $event->getIssueId(), $send_notification, $resolution_id, $status_id, $reason, $event->getUserId());
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
