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
use Eventum\Db\Doctrine;
use Eventum\Event\EventContext;
use Eventum\Event\ResultableEvent;
use Eventum\Event\SystemEvents;
use Eventum\Extension\ExtensionLoader;
use Eventum\Extension\Provider;
use Eventum\LinkFilter\LinkFilter;
use Eventum\Logger\LoggerTrait;
use Eventum\Mail\ImapMessage;
use Eventum\Mail\MailMessage;
use Eventum\Model\Repository\ProjectRepository;
use InvalidArgumentException;
use Laminas\Mail\Address;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Workflow;

/**
 * Extension that adds integration of legacy workflow classes to Extension events
 */
class WorkflowLegacyExtension implements Provider\SubscriberProvider, EventSubscriberInterface
{
    use LoggerTrait;

    /** @var ExtensionLoader */
    private $extensionLoader;
    /** @var ProjectRepository */
    private $projectRepository;

    public function __construct()
    {
        $this->projectRepository = Doctrine::getProjectRepository();
        $this->extensionLoader = Workflow::getExtensionLoader();
    }

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
            /** @see WorkflowLegacyExtension::handleNewEmail */
            SystemEvents::MAIL_CREATED => 'handleNewEmail',
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
            /** @see WorkflowLegacyExtension::canSendNote */
            SystemEvents::ACCESS_ISSUE_NOTE => 'canSendNote',
            /** @see WorkflowLegacyExtension::canCloneIssue */
            SystemEvents::ACCESS_ISSUE_CLONE => 'canCloneIssue',
            /** @see WorkflowLegacyExtension::canChangeAccessLevel */
            SystemEvents::ACCESS_ISSUE_CHANGE_ACCESS => 'canChangeAccessLevel',
            /** @see WorkflowLegacyExtension::handleAuthorizedReplierAdded */
            SystemEvents::AUTHORIZED_REPLIER_ADD => 'handleAuthorizedReplierAdded',
            /** @see WorkflowLegacyExtension::preEmailDownload */
            SystemEvents::MAIL_PROCESS_BEFORE => 'preEmailDownload',
            /** @see WorkflowLegacyExtension::preNoteInsert */
            SystemEvents::NOTE_INSERT_BEFORE => 'preNoteInsert',
            /** @see WorkflowLegacyExtension::shouldAutoAddToNotificationList */
            SystemEvents::PROJECT_NOTIFICATION_AUTO_ADD => 'shouldAutoAddToNotificationList',
            /** @see WorkflowLegacyExtension::getIssueIDForNewEmail */
            SystemEvents::ISSUE_EMAIL_CREATE_OPTIONS => 'getIssueIDForNewEmail',
            /** @see WorkflowLegacyExtension::modifyMailQueue */
            SystemEvents::MAIL_QUEUE_MODIFY => 'modifyMailQueue',
            /** @see WorkflowLegacyExtension::preStatusChange */
            SystemEvents::ISSUE_STATUS_BEFORE => 'preStatusChange',
            /** @see WorkflowLegacyExtension::prePage */
            SystemEvents::PAGE_BEFORE => 'prePage',
            /** @see WorkflowLegacyExtension::getNotificationActions */
            SystemEvents::NOTIFICATION_ACTIONS => 'getNotificationActions',
            /** @see WorkflowLegacyExtension::getIssueFieldsToDisplay */
            SystemEvents::ISSUE_FIELDS_DISPLAY => 'getIssueFieldsToDisplay',
            /** @see WorkflowLegacyExtension::addLinkFilters */
            SystemEvents::ISSUE_LINK_FILTERS => 'addLinkFilters',
            /** @see WorkflowLegacyExtension::canUpdateIssue */
            SystemEvents::ACCESS_ISSUE_UPDATE => 'canUpdateIssue',
            /** @see WorkflowLegacyExtension::canChangeAssignee */
            SystemEvents::ACCESS_ISSUE_CHANGE_ASSIGNEE => 'canChangeAssignee',
            /** @see WorkflowLegacyExtension::getActiveGroup */
            SystemEvents::GROUP_ACTIVE => 'getActiveGroup',
            /** @see WorkflowLegacyExtension::getAccessLevels */
            SystemEvents::ACCESS_LEVELS => 'getAccessLevels',
            /** @see WorkflowLegacyExtension::canAccessIssue */
            SystemEvents::ACCESS_ISSUE => 'canAccessIssue',
            /** @see WorkflowLegacyExtension::getAdditionalAccessSQL */
            SystemEvents::ACCESS_LISTING_SQL => 'getAdditionalAccessSQL',
            /** @see WorkflowLegacyExtension::handleIssueMovedFromProject */
            SystemEvents::ISSUE_MOVE_FROM_PROJECT => 'handleIssueMovedFromProject',
            /** @see WorkflowLegacyExtension::handleIssueMovedToProject */
            SystemEvents::ISSUE_MOVE_TO_PROJECT => 'handleIssueMovedToProject',
            /** @see WorkflowLegacyExtension::getMovedIssueMapping */
            SystemEvents::ISSUE_MOVE_MAPPING => 'getMovedIssueMapping',
        ];
    }

    /**
     * @see Workflow::handleIssueUpdated
     */
    public function handleIssueUpdated(EventContext $event): void
    {
        if (!$backend = $this->getBackend($event)) {
            return;
        }

        $old_details = $event['old_details'];
        $changes = $event['raw_post'];
        $backend->handleIssueUpdated($event->getProjectId(), $event->getIssueId(), $event->getUserId(), $old_details, $changes);
    }

    /**
     * @see Workflow::preIssueUpdated
     */
    public function preIssueUpdated(EventContext $event): void
    {
        if (!$backend = $this->getBackend($event)) {
            return;
        }

        $result = $backend->preIssueUpdated($event->getProjectId(), $event->getIssueId(), $event->getUserId(), $event['changes']);
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
     * @see Workflow::canSendNote
     */
    public function canSendNote(ResultableEvent $event): void
    {
        if (!$backend = $this->getBackend($event)) {
            return;
        }

        /** @var Address $address */
        $address = $event->getSubject();
        $email = $address->getEmail();
        /** @var MailMessage $mailMessage */
        $mail = $event['mail'];
        $result = $backend->canSendNote($event->getProjectId(), $event->getIssueId(), $email, $mail);
        if ($result !== null) {
            $event->setResult($result);
        }
    }

    /**
     * @see Workflow::canCloneIssue
     */
    public function canCloneIssue(ResultableEvent $event): void
    {
        if (!$backend = $this->getBackend($event)) {
            return;
        }

        $result = $backend->canCloneIssue($event->getProjectId(), $event->getIssueId(), $event->getUserId());
        if ($result !== null) {
            $event->setResult($result);
        }
    }

    /**
     * @see Workflow::canChangeAccessLevel
     */
    public function canChangeAccessLevel(ResultableEvent $event): void
    {
        if (!$backend = $this->getBackend($event)) {
            return;
        }

        $result = $backend->canChangeAccessLevel($event->getProjectId(), $event->getIssueId(), $event->getUserId());
        if ($result !== null) {
            $event->setResult($result);
        }
    }

    /**
     * @see Workflow::canChangeAssignee
     */
    public function canChangeAssignee(ResultableEvent $event): void
    {
        if (!$backend = $this->getBackend($event)) {
            return;
        }

        $result = $backend->canChangeAssignee($event->getProjectId(), $event->getIssueId(), $event->getUserId());
        if ($result !== null) {
            $event->setResult($result);
        }
    }

    /**
     * @see Workflow::canChangeAccessLevel
     */
    public function handleAuthorizedReplierAdded(ResultableEvent $event): void
    {
        if (!$backend = $this->getBackend($event)) {
            return;
        }

        /** @var Address $address */
        $address = $event->getSubject();
        $email = $address->getEmail();
        $result = $backend->handleAuthorizedReplierAdded($event->getProjectId(), $event->getIssueId(), $email);
        if ($result !== null) {
            $event->setResult($result);
        }

        // assign back, in case it was modified
        if ($email !== $address->getEmail()) {
            $event['email'] = $email;
        }
    }

    /**
     * @see Workflow::preEmailDownload
     */
    public function preEmailDownload(EventContext $event): void
    {
        if (!$backend = $this->getBackend($event)) {
            return;
        }

        /** @var ImapMessage $mail */
        $mail = $event->getSubject();

        $result = $backend->preEmailDownload($event->getProjectId(), $mail);
        if ($result === -1) {
            $event->stopPropagation();
        }
    }

    /**
     * @see Workflow::preNoteInsert
     */
    public function preNoteInsert(ResultableEvent $event): void
    {
        if (!$backend = $this->getBackend($event)) {
            return;
        }

        /** @var array $data */
        $data = $event->getSubject();
        $result = $backend->preNoteInsert($event->getProjectId(), $event->getIssueId(), $data);
        if ($result !== null) {
            $event->setResult($result);
        }

        // assign back, in case it was modified
        $event['data'] = $data;
    }

    /**
     * @see Workflow::shouldAutoAddToNotificationList
     */
    public function shouldAutoAddToNotificationList(ResultableEvent $event): void
    {
        if (!$backend = $this->getBackend($event)) {
            return;
        }

        $result = $backend->shouldAutoAddToNotificationList($event->getProjectId());
        if ($result !== null) {
            $event->setResult($result);
        }
    }

    /**
     * @see Workflow::getIssueIDForNewEmail
     */
    public function getIssueIDForNewEmail(ResultableEvent $event): void
    {
        if (!$backend = $this->getBackend($event)) {
            return;
        }

        /** @var MailMessage $mail */
        $mail = $event->getSubject();
        $account = $event['account'];
        $result = $backend->getIssueIDForNewEmail($event->getProjectId(), $account, $mail);
        if ($result !== null) {
            $event->setResult($result);
        }
    }

    /**
     * @see Workflow::modifyMailQueue
     */
    public function modifyMailQueue(EventContext $event): void
    {
        if (!$backend = $this->getBackend($event)) {
            return;
        }

        /** @var MailMessage $mail */
        $mail = $event->getSubject();
        /** @var Address $recipient */
        $address = $event['address'];
        /** @var array $options */
        $options = $event['options'];
        $backend->modifyMailQueue($event->getProjectId(), $address->getEmail(), $mail, $options);
    }

    /**
     * @see Workflow::preStatusChange
     */
    public function preStatusChange(ResultableEvent $event): void
    {
        if (!$backend = $this->getBackend($event)) {
            return;
        }

        $issue_id = $event->getIssueId();
        $status_id = $event['status_id'];
        $notify = $event['notify'];
        $result = $backend->preStatusChange($event->getProjectId(), $issue_id, $status_id, $notify);
        if ($result !== null) {
            $event->setResult($result);
        }

        // assign back, in case they were modified
        $event['issue_id'] = $issue_id;
        $event['status_id'] = $status_id;
        $event['notify'] = $notify;
    }

    /**
     * @see Workflow::prePage
     */
    public function prePage(EventContext $event): void
    {
        if (!$backend = $this->getBackend($event)) {
            return;
        }

        $page_name = $event->getSubject();
        $backend->prePage($event->getProjectId(), $page_name);
    }

    /**
     * @see Workflow::getNotificationActions
     */
    public function getNotificationActions(ResultableEvent $event): void
    {
        if (!$backend = $this->getBackend($event)) {
            return;
        }

        $source = $event['source'];
        $email = $event['email'];
        $result = $backend->getNotificationActions($event->getProjectId(), $event->getIssueId(), $email, $source);
        if ($result !== null) {
            $event->setResult($result);
        }
    }

    /**
     * @see Workflow::getIssueFieldsToDisplay
     */
    public function getIssueFieldsToDisplay(ResultableEvent $event): void
    {
        if (!$backend = $this->getBackend($event)) {
            return;
        }

        $location = $event->getSubject();
        $result = $backend->getIssueFieldsToDisplay($event->getProjectId(), $event->getIssueId(), $location);
        if ($result !== null) {
            $event->setResult($result);
        }
    }

    /**
     * @see Workflow::addLinkFilters
     */
    public function addLinkFilters(EventContext $event): void
    {
        if (!$backend = $this->getBackend($event)) {
            return;
        }

        /** @var LinkFilter $linkFilter */
        $linkFilter = $event->getSubject();
        $linkFilter->addRules($backend->getLinkFilters($event->getProjectId()));
    }

    /**
     * @see Workflow::canUpdateIssue
     */
    public function canUpdateIssue(ResultableEvent $event): void
    {
        if (!$backend = $this->getBackend($event)) {
            return;
        }

        $result = $backend->canUpdateIssue($event->getProjectId(), $event->getIssueId(), $event->getUserId());
        if ($result !== null) {
            $event->setResult($result);
        }
    }

    /**
     * @see Workflow::getActiveGroup
     */
    public function getActiveGroup(ResultableEvent $event): void
    {
        if (!$backend = $this->getBackend($event)) {
            return;
        }

        $result = $backend->getActiveGroup($event->getProjectId());
        if ($result !== null) {
            $event->setResult($result);
        }
    }

    /**
     * @see Workflow::getAccessLevels
     */
    public function getAccessLevels(ResultableEvent $event): void
    {
        if (!$backend = $this->getBackend($event)) {
            return;
        }

        $result = $backend->getAccessLevels($event->getProjectId());
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
     * @see Workflow::handleNewEmail
     */
    public function handleNewEmail(EventContext $event): void
    {
        if (!$backend = $this->getBackend($event)) {
            return;
        }

        /** @var MailMessage $mail */
        $mail = $event->getSubject();
        $closing = $event['closing'];
        $row = $event['data'];
        $backend->handleNewEmail($event->getProjectId(), $event->getIssueId(), $mail, $row, $closing);
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
     * @see Workflow::handleIssueMovedFromProject
     */
    public function handleIssueMovedFromProject(EventContext $event): void
    {
        if (!$backend = $this->getBackend($event)) {
            return;
        }

        $prj_id = $event['new_prj_id'];
        $backend->handleIssueMovedFromProject($event->getProjectId(), $event->getIssueId(), $prj_id);
    }

    /**
     * @see Workflow::handleIssueMovedToProject
     */
    public function handleIssueMovedToProject(EventContext $event): void
    {
        if (!$backend = $this->getBackend($event)) {
            return;
        }

        $prj_id = $event['old_prj_id'];
        $backend->handleIssueMovedToProject($event->getProjectId(), $event->getIssueId(), $prj_id);
    }

    /**
     * @see Workflow::getMovedIssueMapping
     */
    public function getMovedIssueMapping(ResultableEvent $event): void
    {
        if (!$backend = $this->getBackend($event)) {
            return;
        }

        $mapping = $event->getResult();
        $old_prj_id = $event['old_prj_id'];
        $result = $backend->getMovedIssueMapping($event->getProjectId(), $event->getIssueId(), $mapping, $old_prj_id);
        if ($result !== null) {
            $event->setResult($result);
        }
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

    /**
     * @see Workflow::getAdditionalAccessSQL
     */
    public function getAdditionalAccessSQL(ResultableEvent $event): void
    {
        if (!$backend = $this->getBackend($event)) {
            return;
        }

        $result = $backend->getAdditionalAccessSQL($event->getProjectId(), $event->getUserId());

        if ($result !== null) {
            $event->setResult($result);
        }
    }

    private function getBackend(EventContext $event): ?Abstract_Workflow_Backend
    {
        static $cache = [];

        $prj_id = $event->getProjectId();

        // bunch of code calling without project id context
        if (!$prj_id) {
            return null;
        }

        if (array_key_exists($prj_id, $cache)) {
            return $cache[$prj_id];
        }

        return $cache[$prj_id] = $this->loadBackend($prj_id);
    }

    private function loadBackend(int $prj_id): ?Abstract_Workflow_Backend
    {
        try {
            $project = $this->projectRepository->findById($prj_id);
            $backendName = $project->getWorkflowBackend();
            if (!$backendName) {
                return null;
            }

            /** @var Abstract_Workflow_Backend $backend */
            $backend = $this->extensionLoader->createInstance($backendName);
            $backend->prj_id = $prj_id;
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage(), ['exception' => $e]);

            return null;
        }

        return $backend;
    }
}
