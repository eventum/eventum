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
     * @inheritdoc
     */
    protected function configure()
    {
        $request = $this->getRequest();

        $this->issue_id = $request->request->getInt('issue_id') ?: $request->query->getInt('issue_id');
        $this->issue_details = Issue::getDetails($this->issue_id);
        $this->cat = $request->request->get('cat') ?: $request->query->get('cat');
    }

    /**
     * @inheritdoc
     */
    protected function canAccess()
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
     * @inheritdoc
     */
    protected function defaultAction()
    {
        $details = Issue::getDetails($this->issue_id);
        $this->tpl->assign('issue', $details);

        Workflow::prePage($this->prj_id, 'post_note');

        $request = $this->getRequest();
        $get = $request->query;

        if ($this->cat == 'post_result' && ($post_result = $get->getInt('post_result'))) {
            $this->tpl->assign('post_result', $post_result);
        } elseif ($this->cat == 'post_note') {
            $this->postNoteAction();
        } elseif ($this->cat == 'reply' && ($note_id = $get->getInt('id'))) {
            $this->replyAction($note_id);
        } elseif ($this->cat == 'email_reply' && ($sup_id = $get->getInt('id')) && ($ema_id = $get->getInt('ema_id'))) {
            $this->replyEmailAction($sup_id, $ema_id);
        } elseif ($this->cat == 'issue_reply') {
            $this->replyIssueAction();
        }

        if (!$this->reply_subject) {
            // TRANSLATORS: %1 = issue summary
            $this->reply_subject = $details['iss_summary'];
        }
    }

    private function replyAction($note_id)
    {
        $note = Note::getDetails($note_id);
        $header = Misc::formatReplyPreamble($note['timestamp'], $note['not_from']);
        $note['not_body'] = $header . Misc::formatReply($note['not_note']);
        $this->tpl->assign(
            array(
                'note' => $note,
                'parent_note_id' => $note_id,
            )
        );
        $this->reply_subject = $note['not_title'];
    }

    private function replyEmailAction($sup_id, $ema_id)
    {
        $email = Support::getEmailDetails($ema_id, $sup_id);
        $header = Misc::formatReplyPreamble($email['timestamp'], $email['sup_from']);
        $note = array();
        $note['not_body'] = $header . Misc::formatReply($email['message']);
        $this->tpl->assign(array(
            'note'           => $note
        ));
        $this->reply_subject = $email['sup_subject'];
    }

    private function replyIssueAction()
    {
        $header = Misc::formatReplyPreamble($this->issue_details['iss_created_date'], $this->issue_details['reporter']);
        $note = array();
        $note['not_body'] = $header . Misc::formatReply($this->issue_details['iss_original_description']);
        $this->tpl->assign(array(
            'note'           => $note
        ));
        $this->reply_subject = $this->issue_details['iss_summary'];
    }

    private function postNoteAction()
    {
        $request = $this->getRequest();
        $post = $request->request;
        $get = $request->query;

        // change status
        if ($status = $post->getInt('new_status')) {
            $this->setIssueStatus($status);
        }

        $res = Note::insertFromPost($this->usr_id, $this->issue_id);

        $issue_field = $post->get('issue_field') ?: $get->get('issue_field');
        Issue_Field::updateValues($this->issue_id, 'post_note', $issue_field);

        if ($res == -1) {
            Misc::setMessage(ev_gettext('An error occurred while trying to run your query'), Misc::MSG_ERROR);
        } else {
            Misc::setMessage(ev_gettext('Thank you, the internal note was posted successfully.'), Misc::MSG_INFO);
        }
        $this->tpl->assign('post_result', $res);

        if ($post->get('time_spent')) {
            $this->addTimeEntry();
        }

        $this->redirect(
            'post_note.php', array(
                'cat' => 'post_result',
                'issue_id' => $this->issue_id,
                'post_result' => $res,
            )
        );
    }

    /**
     * @param int $status
     */
    private function setIssueStatus($status)
    {
        $res = Issue::setStatus($this->issue_id, $status);
        if ($res == -1) {
            return;
        }

        $status_title = Status::getStatusTitle($status);
        History::add(
            $this->issue_id, $this->usr_id, 'status_changed',
            "Status changed to '{status}' by {user} when sending a note", array(
                'status' => $status_title,
                'user' => User::getFullName($this->usr_id)
            )
        );
    }

    /**
     * enter the time tracking entry about this phone support entry
     */
    private function addTimeEntry()
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
     * @inheritdoc
     */
    protected function prepareTemplate()
    {
        $reply_subject = Mail_Helper::removeExcessRe(ev_gettext('Re: %1$s', $this->reply_subject), true);
        $this->tpl->assign(
            array(
                'issue_id' => $this->issue_id,
                'reply_subject' => $reply_subject,
                'from' => User::getFromHeader($this->usr_id),
                'users' => Project::getUserAssocList($this->prj_id, 'active', User::ROLE_CUSTOMER),
                'subscribers' => Notification::getSubscribers($this->issue_id, false, User::ROLE_USER),
                'statuses' => Status::getAssocStatusList($this->prj_id, false),
                'current_issue_status' => Issue::getStatusID($this->issue_id),
                'time_categories' => Time_Tracking::getAssocCategories($this->prj_id),
                'note_category_id' => Time_Tracking::getCategoryId($this->prj_id, 'Note Discussion'),
                'issue_fields' => Issue_Field::getDisplayData($this->issue_id, 'post_note'),
            )
        );
    }
}
