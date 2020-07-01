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

use Auth;
use Authorized_Replier;
use Eventum\Attachment\AttachmentManager;
use Eventum\Db\DatabaseException;
use Filter;
use History;
use Issue;
use Note;
use Notification;
use Phone_Support;
use SCM;
use Status;
use Support;
use Time_Tracking;
use User;
use Workflow;

class PopupController extends BaseController
{
    /** @var string */
    protected $tpl_name = 'popup.tpl.html';

    /** @var int */
    private $issue_id;

    /** @var string */
    private $cat;

    /** @var int */
    private $id;

    /** @var int */
    private $status_id;

    /** @var int */
    private $isr_id;

    /** @var array */
    private $items;

    /** @var int */
    private $usr_id;

    /** @var int */
    private $prj_id;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $request = $this->getRequest();

        $this->issue_id = $request->query->getInt('iss_id') ?: $request->request->getInt('issue_id');
        $this->cat = $request->query->get('cat') ?: $request->request->get('cat');
        $this->id = $request->query->getInt('id');
        $this->status_id = $request->query->getInt('new_sta_id');
        $this->isr_id = $request->request->getInt('isr_id');
        $this->items = $request->request->get('item');
    }

    /**
     * {@inheritdoc}
     */
    protected function canAccess(): bool
    {
        Auth::checkAuthentication(null, true);

        $this->usr_id = Auth::getUserID();
        $this->prj_id = Auth::getCurrentProject();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction(): void
    {
        switch ($this->cat) {
            case 'delete_note':
                $res = Note::remove($this->id);
                $this->tpl->assign('note_delete_result', $res);
                break;

            case 'delete_time':
                $res = Time_Tracking::removeTimeEntry($this->id, $this->usr_id);
                $this->tpl->assign('time_delete_result', $res);
                break;

            case 'bulk_update':
                $res = Issue::bulkUpdate();
                $this->tpl->assign('bulk_update_result', $res);
                break;

            case 'save_filter':
                $res = Filter::save();
                $this->tpl->assign('save_filter_result', $res);
                break;

            case 'delete_filter':
                $res = Filter::remove();
                $this->tpl->assign('delete_filter_result', $res);
                break;

            case 'remove_support_email':
                $res = Support::removeAssociation();
                $this->tpl->assign('remove_association_result', $res);
                break;

            case 'delete_attachment':
                $res = AttachmentManager::removeAttachmentGroup($this->id);
                $this->tpl->assign('remove_attachment_result', $res);
                break;

            case 'delete_file':
                $res = AttachmentManager::removeAttachment($this->id);
                $this->tpl->assign('remove_file_result', $res);
                break;

            case 'remove_checkin':
                $res = SCM::remove($this->items);
                $this->tpl->assign('remove_checkin_result', $res);
                break;

            case 'unassign':
                $details = Issue::getDetails($this->issue_id);
                $res = Issue::deleteUserAssociation($this->issue_id, $this->usr_id);
                $assigned_usr_ids = Issue::getAssignedUserIDs($this->issue_id);
                Workflow::handleAssignmentChange(
                    $this->prj_id,
                    $this->issue_id,
                    $this->usr_id,
                    $details,
                    $assigned_usr_ids
                );
                Notification::notifyAssignmentChange($this->issue_id, $details['assigned_users'], $assigned_usr_ids);
                $this->tpl->assign('unassign_result', $res);
                break;

            case 'remove_email':
                $res = Support::removeEmails();
                $this->tpl->assign('remove_email_result', $res);
                break;

            case 'clear_duplicate':
                $res = Issue::clearDuplicateStatus($this->issue_id);
                $this->tpl->assign('clear_duplicate_result', $res);
                break;

            case 'delete_phone':
                $res = Phone_Support::remove($this->id);
                $this->tpl->assign('delete_phone_result', $res);
                break;

            case 'new_status':
                $res = Issue::setStatus($this->issue_id, $this->status_id, true);
                if ($res == 1) {
                    History::add(
                        $this->issue_id,
                        $this->usr_id,
                        'status_changed',
                        "Issue manually set to status '{status}' by {user}",
                        [
                            'status' => Status::getStatusTitle($this->status_id),
                            'user' => User::getFullName($this->usr_id),
                        ]
                    );
                }
                $this->tpl->assign('new_status_result', $res);
                break;

            case 'authorize_reply':
                $res = Authorized_Replier::addUser($this->issue_id, $this->usr_id);
                $this->tpl->assign('authorize_reply_result', $res);
                break;

            case 'remove_quarantine':
                if (Auth::getCurrentRole() > User::ROLE_DEVELOPER) {
                    try {
                        Issue::setQuarantine($this->issue_id, 0);
                        $res = 1;
                    } catch (DatabaseException $e) {
                        $res = -1;
                    }
                    $this->tpl->assign('remove_quarantine_result', $res);
                }

                break;

            case 'selfnotify':
                if (Issue::canAccess($this->issue_id, $this->usr_id)) {
                    $actions = Notification::getDefaultActions($this->issue_id);
                    $res = Notification::subscribeUser($this->usr_id, $this->issue_id, $this->usr_id, $actions);
                    $this->tpl->assign('selfnotify_result', $res);
                }
                break;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(): void
    {
        $this->tpl->assign(
            [
                'cat' => $this->cat,
            ]
        );
    }
}
