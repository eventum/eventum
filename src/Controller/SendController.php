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

namespace Eventum\Controller;

use Access;
use Auth;
use Draft;
use Email_Account;
use Email_Response;
use Eventum\Attachment\AttachmentManager;
use History;
use Issue;
use Mail_Helper;
use Misc;
use Note;
use Notification;
use Prefs;
use Status;
use Support;
use Time_Tracking;
use User;
use Workflow;

/**
 * Class handling send.php
 */
class SendController extends BaseController
{
    /** @var string */
    protected $tpl_name = 'send.tpl.html';

    /** @var string */
    private $cat;

    /** @var int */
    private $issue_id;

    /** @var int */
    private $usr_id;

    /** @var int */
    private $prj_id;

    // TODO: $ema_id is likely not needed
    /** @var int */
    private $ema_id;

    /** @var int */
    private $note_id;

    /**
     * create variables from request, etc
     */
    protected function configure()
    {
        $request = $this->getRequest();
        $this->issue_id = $request->request->getInt('issue_id') ?: $request->query->getInt('issue_id');
        $this->cat = $request->request->get('cat') ?: $request->query->get('cat');
        $this->ema_id = (int) $request->get('ema_id');
        $this->note_id = $request->get('note_id');
    }

    protected function canAccess()
    {
        Auth::checkAuthentication();

        $this->prj_id = Auth::getCurrentProject();
        $this->usr_id = Auth::getUserID();

        if ($this->issue_id) {
            $issue_access = Issue::canAccess($this->issue_id, $this->usr_id);
            if ($issue_access === true && $this->note_id) {
                return (Access::canViewInternalNotes($this->issue_id, $this->usr_id) && Access::canAccessAssociateEmails($this->usr_id));
            }

            return $issue_access;
        }

        return Access::canAccessAssociateEmails($this->usr_id);
    }

    protected function defaultAction()
    {
        Workflow::prePage($this->prj_id, 'send_email');

        // since emails associated with issues are sent to the notification list,
        // not the to: field, set the to field to be blank
        // this field should already be blank, but may also be unset.
        // FIXME: move this to proper 'cat' action
        if ($this->issue_id) {
            $_POST['to'] = '';
        }

        switch ($this->cat) {
            case 'send_email':
                $this->sendEmailAction();
                break;
            case 'save_draft':
                $this->saveDraftAction();
                break;
            case 'update_draft':
                $this->updateDraftAction();
                break;
            case 'view_draft':
                $this->viewDraftAction();
                break;
            case 'create_draft':
                $this->createDraftAction();
                break;
            case 'reply_to_note':
                $this->replyNoteAction();
                break;
            default:
                $this->otherAction();
        }

        if ($this->cat == 'reply') {
            $this->replyAction();
        }
    }

    protected function prepareTemplate()
    {
        if ($this->issue_id) {
            $sender_details = User::getDetails($this->usr_id);
            // list the available statuses
            $this->tpl->assign(
                [
                    'issue_id' => $this->issue_id,

                    'statuses' => Status::getAssocStatusList($this->prj_id, false),
                    'current_issue_status' => Issue::getStatusID($this->issue_id),
                    // set if the current user is allowed to send emails on this issue or not
                    'can_send_email' => Support::isAllowedToEmail($this->issue_id, $sender_details['usr_email']),
                    'subscribers' => Notification::getSubscribers($this->issue_id, 'emails'),
                    'should_auto_add_to_nl' => Workflow::shouldAutoAddToNotificationList($this->prj_id),
                ]
            );
        }

        $this->tpl->assign('ema_id', $this->ema_id);

        $user_prefs = Prefs::get($this->usr_id);

        $this->tpl->assign(
            [
                'from' => User::getFromHeader($this->usr_id),
                'canned_responses' => Email_Response::getAssocList($this->prj_id),
                'js_canned_responses' => Email_Response::getAssocListBodies($this->prj_id),
                'issue_access' => Access::getIssueAccessArray($this->issue_id, $this->usr_id),
                'max_attachment_size' => AttachmentManager::getMaxAttachmentSize(),
                'max_attachment_bytes' => AttachmentManager::getMaxAttachmentSize(true),
                'time_categories' => Time_Tracking::getAssocCategories($this->prj_id),
                'email_category_id' => Time_Tracking::getCategoryId($this->prj_id, 'Email Discussion'),
            ]
        );

        // don't add signature if it already exists. Note: This won't handle multiple user duplicate sigs.
        if (!empty($draft['emd_body']) && $user_prefs['auto_append_email_sig'] == 1
            && strpos($draft['emd_body'], $user_prefs['email_signature']) !== false
        ) {
            $this->tpl->assign('body_has_sig_already', 1);
        }
    }

    private function sendEmailAction()
    {
        $post = $this->getRequest()->request;

        $iaf_ids = $this->attach->getAttachedFileIds();

        $options = [
            'parent_sup_id' => $post->get('parent_id'),
            'iaf_ids' => $iaf_ids,
            'add_unknown' => $post->get('add_unknown') == 'yes',
            'ema_id' => $post->has('ema_id') ? $post->getInt('ema_id') : null,
        ];

        $res = Support::sendEmail(
            $this->issue_id,
            $post->get('type'),
            $post->get('from'),
            $post->get('to', ''),
            $post->get('cc'),
            Mail_Helper::cleanSubject($post->get('subject')),
            $post->get('message'),
            $options
        );

        $this->tpl->assign('send_result', $res);
        $this->tpl->assign('garlic_prefix', $post->has('garlic_prefix') ? $post->get('garlic_prefix') : '');

        $new_status = $post->get('new_status');
        if ($new_status && Access::canChangeStatus($this->issue_id, $this->usr_id)) {
            $res = Issue::setStatus($this->issue_id, $new_status);
            if ($res == 1) {
                $status_title = Status::getStatusTitle($new_status);
                History::add(
                    $this->issue_id, $this->usr_id, 'status_changed',
                    "Status changed to '{status}' by {user} when sending an email", [
                        'status' => $status_title,
                        'user' => User::getFullName($this->usr_id),
                    ]
                );
            }
        }

        // remove the existing email draft, if appropriate
        $draft_id = $post->getInt('draft_id');
        if ($draft_id) {
            Draft::remove($draft_id);
        }

        // enter the time tracking entry about this new email
        $summary = ev_gettext('Time entry inserted when sending outgoing email.');
        $this->addTimeTracking($summary);

        return true;
    }

    private function saveDraftAction()
    {
        $post = $this->getRequest()->request;

        $res = Draft::saveEmail(
            $this->issue_id,
            $post->get('to'), $post->get('cc'), Mail_Helper::cleanSubject($post->get('subject')), $post->get('message'),
            $post->get('parent_id')
        );
        $this->tpl->assign('draft_result', $res);

        $summary = ev_gettext('Time entry inserted when saving an email draft.');
        $this->addTimeTracking($summary);
    }

    private function updateDraftAction()
    {
        $post = $this->getRequest()->request;
        $res = Draft::update(
            $this->issue_id,
            $post->get('draft_id'), $post->get('to'), $post->get('cc'), $post->get('subject'), $post->get('message'),
            $post->get('parent_id')
        );
        $this->tpl->assign('draft_result', $res);

        $summary = ev_gettext('Time entry inserted when saving an email draft.');
        $this->addTimeTracking($summary);
    }

    private function viewDraftAction()
    {
        $draft = Draft::getDetails($_GET['id']);
        $email = [
            'sup_subject' => $draft['emd_subject'],
            'seb_body' => $draft['emd_body'],
            'sup_from' => $draft['to'],
            'cc' => implode('; ', $draft['cc']),
        ];

        // try to guess the correct email account to be associated with this email
        if (!empty($draft['emd_sup_id'])) {
            $this->ema_id = Email_Account::getAccountByEmail($draft['emd_sup_id']);
        } else {
            // if we are not replying to an existing message, just get the first email account you can find...
            $this->ema_id = Email_Account::getEmailAccount();
        }

        $this->tpl->assign(
            [
                'draft_id' => $_GET['id'],
                'email' => $email,
                'parent_email_id' => $draft['emd_sup_id'],
                'draft_status' => $draft['emd_status'],
            ]
        );

        if ($draft['emd_status'] != 'pending') {
            $this->tpl->assign('read_only', 1);
        }
    }

    private function createDraftAction()
    {
        $this->tpl->assign('hide_email_buttons', 'yes');
    }

    private function otherAction()
    {
        $get = $this->getRequest()->query;

        if (!$get->has('id')) {
            return;
        }

        $email = Support::getEmailDetails($get->getInt('id'));
        $header = Misc::formatReplyPreamble($email['timestamp'], $email['sup_from']);
        $email['seb_body'] = $header . Misc::formatReply($email['seb_body']);
        $this->tpl->assign(
            [
                'email' => $email,
                'parent_email_id' => $get->getInt('id'),
            ]
        );
    }

    /**
     * special handling when someone tries to 'reply' to an issue
     */
    private function replyAction()
    {
        $details = Issue::getReplyDetails($this->issue_id);
        if (!$details) {
            return;
        }

        $header = Misc::formatReplyPreamble($details['created_date_ts'], $details['reporter']);
        $details['seb_body'] = $header . Misc::formatReply($details['description']);
        $details['sup_from'] = Mail_Helper::getFormattedName($details['reporter'], $details['reporter_email']);
        // TRANSLATORS: %1: issue_id
        $extra_title = ev_gettext('Issue #%1$s: Reply', $this->issue_id);
        $this->tpl->assign(
            [
                'email' => $details,
                'parent_email_id' => 0,
                'extra_title' => $extra_title,
            ]
        );
    }

    /**
     * special handling when someone tries to 'reply' to a note
     */
    private function replyNoteAction()
    {
        $note = Note::getDetails($this->note_id);
        if (!$note) {
            return;
        }

        $header = Misc::formatReplyPreamble($note['timestamp'], $note['not_from']);
        $details['reply_subject'] = $note['not_title'];
        $details['seb_body'] = $header . Misc::formatReply($note['not_note']);
        // TRANSLATORS: %1: issue_id
        $extra_title = ev_gettext('Issue #%1$s: Reply', $this->issue_id);
        $this->tpl->assign(
            [
                'note_id' => $this->note_id,
                'email' => $details,
                'extra_title' => $extra_title,
            ]
        );
    }

    /**
     * Enter the time tracking entry about this new email
     * @param string $default_summary
     */
    private function addTimeTracking($default_summary)
    {
        $post = $this->getRequest()->request;

        $time_spent = (int) $post->get('time_spent');
        if (!$time_spent) {
            return;
        }

        $summary = $post->get('time_summary') ?: $default_summary;
        $ttc_id = (int) $post->get('time_category');
        Time_Tracking::addTimeEntry($this->issue_id, $ttc_id, $time_spent, null, $summary);
    }
}
