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
use History;
use Issue;
use Issue_Field;
use Mail_Helper;
use Misc;
use Note;
use Notification;
use Project;
use Status;
use Support;
use Time_Tracking;
use User;
use Workflow;

class PostNoteController extends BaseController
{
    /** @var string */
    protected $tpl_name = 'post_note.tpl.html';

    /** @var int */
    private $issue_id;

    /** @var array */
    private $issue_details;

    /** @var int */
    private $usr_id;

    /** @var int */
    private $prj_id;

    /** @var string */
    private $cat;

    /** @var string */
    private $reply_subject;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $request = $this->getRequest();

        $this->issue_id = $request->request->getInt('issue_id') ?: $request->query->getInt('issue_id');
        $this->issue_details = Issue::getDetails($this->issue_id);
        $this->cat = $request->request->get('cat') ?: $request->query->get('cat');
    }

    /**
     * {@inheritdoc}
     */
    protected function canAccess(): bool
    {
        Auth::checkAuthentication(null, true);

        $this->prj_id = Auth::getCurrentProject();
        $this->usr_id = Auth::getUserID();

        if (!Issue::canAccess($this->issue_id, $this->usr_id)) {
            return false;
        }

        // FIXME: superfluous?
        if (Auth::getCurrentRole() <= User::ROLE_CUSTOMER) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction(): void
    {
        $details = Issue::getDetails($this->issue_id);
        $this->tpl->assign('issue', $details);

        Workflow::prePage($this->prj_id, 'post_note');

        $request = $this->getRequest();
        $get = $request->query;

        if ($this->cat == 'post_result' && ($post_result = $get->getInt('post_result'))) {
            $this->tpl->assign('post_result', $post_result);
            $this->tpl->assign('garlic_prefix', $get->get('garlic_prefix', ''));
        } elseif ($this->cat == 'post_note') {
            $this->postNoteAction();
        } elseif ($this->cat == 'reply' && ($note_id = $get->getInt('id'))) {
            $this->replyAction($note_id);
        } elseif ($this->cat == 'email_reply' && ($sup_id = $get->getInt('id'))) {
            $this->replyEmailAction($sup_id);
        } elseif ($this->cat == 'issue_reply') {
            $this->replyIssueAction();
        }

        if (!$this->reply_subject) {
            // TRANSLATORS: %1 = issue summary
            $this->reply_subject = $details['iss_summary'];
        }
    }

    /**
     * @param int $note_id
     */
    private function replyAction($note_id): void
    {
        $note = Note::getDetails($note_id);
        $header = Misc::formatReplyPreamble($note['timestamp'], $note['not_from']);
        $note['not_body'] = $header . Misc::formatReply($note['not_note']);
        $this->tpl->assign(
            [
                'note' => $note,
                'parent_note_id' => $note_id,
            ]
        );
        $this->reply_subject = $note['not_title'];
    }

    /**
     * @param int $sup_id
     */
    private function replyEmailAction($sup_id): void
    {
        $email = Support::getEmailDetails($sup_id);
        $header = Misc::formatReplyPreamble($email['timestamp'], $email['sup_from']);
        $note = [];
        $note['not_body'] = $header . Misc::formatReply($email['message']);
        $this->tpl->assign([
            'note' => $note,
            'sup_id' => $sup_id,
        ]);
        $this->reply_subject = $email['sup_subject'];
    }

    private function replyIssueAction(): void
    {
        $header = Misc::formatReplyPreamble($this->issue_details['iss_created_date'], $this->issue_details['reporter']);
        $note = [];
        $note['not_body'] = $header . Misc::formatReply($this->issue_details['iss_original_description']);
        $this->tpl->assign([
            'note' => $note,
        ]);
        $this->reply_subject = $this->issue_details['iss_summary'];
    }

    private function postNoteAction(): void
    {
        $request = $this->getRequest();
        $post = $request->request;
        $get = $request->query;

        // change status
        if ($status = $post->getInt('new_status')) {
            $this->setIssueStatus($status);
        }

        $options = [
            'parent_id' => $post->get('parent_id', null),
            'add_extra_recipients' => $post->get('add_extra_recipients', '') === 'yes',
            'cc' => $post->get('note_cc'),
        ];

        $res = Note::insertNote(
            $this->usr_id,
            $this->issue_id,
            Mail_Helper::cleanSubject($post->get('title')),
            $post->get('note'),
            $options
        );

        $issue_field = $post->get('issue_field') ?: $get->get('issue_field');
        Issue_Field::updateValues($this->issue_id, 'post_note', $issue_field ?: []);

        if ($res == -1) {
            $this->messages->addErrorMessage(ev_gettext('An error occurred while trying to run your query'));
        } else {
            $this->messages->addInfoMessage(ev_gettext('Thank you, the internal note was posted successfully.'));
        }
        $this->tpl->assign('post_result', $res);

        if ($post->get('time_spent')) {
            $this->addTimeEntry();
        }

        $this->redirect(
            'post_note.php',
            [
                'cat' => 'post_result',
                'issue_id' => $this->issue_id,
                'post_result' => $res,
                'garlic_prefix' => $post->get('garlic_prefix', ''),
            ]
        );
    }

    /**
     * @param int $status
     */
    private function setIssueStatus($status): void
    {
        $res = Issue::setStatus($this->issue_id, $status);
        if ($res != 1) {
            return;
        }

        $status_title = Status::getStatusTitle($status);
        History::add(
            $this->issue_id,
            $this->usr_id,
            'status_changed',
            "Status changed to '{status}' by {user} when sending a note",
            [
                'status' => $status_title,
                'user' => User::getFullName($this->usr_id),
            ]
        );
    }

    /**
     * enter the time tracking entry about this phone support entry
     */
    private function addTimeEntry(): void
    {
        $post = $this->getRequest()->request;

        $default_summary = ev_gettext('Time entry inserted when sending an internal note.');
        $summary = $post->get('time_summary') ?: $default_summary;
        $time_spent = $post->getInt('time_spent');
        $date = $post->get('date');
        $ttc_id = $post->getInt('time_category');

        Time_Tracking::addTimeEntry($this->issue_id, $ttc_id, $time_spent, $date, $summary);
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(): void
    {
        $reply_subject = Mail_Helper::removeExcessRe(ev_gettext('Re: %1$s', $this->reply_subject), true);
        $this->tpl->assign(
            [
                'issue_id' => $this->issue_id,
                'reply_subject' => $reply_subject,
                'from' => User::getFromHeader($this->usr_id),
                'users' => Project::getUserAssocList($this->prj_id, 'active', User::ROLE_CUSTOMER),
                'subscribers' => Notification::getSubscribers($this->issue_id, false, User::ROLE_USER),
                'statuses' => Workflow::getAllowedStatuses($this->prj_id, $this->issue_id),
                'current_issue_status' => Issue::getStatusID($this->issue_id),
                'time_categories' => Time_Tracking::getAssocCategories($this->prj_id),
                'note_category_id' => Time_Tracking::getCategoryId($this->prj_id, 'Note Discussion'),
                'issue_fields' => Issue_Field::getDisplayData($this->issue_id, Issue_Field::LOCATION_POST_NOTE),
            ]
        );
    }
}
