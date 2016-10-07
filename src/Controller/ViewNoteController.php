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
use Issue;
use Mail_Queue;
use Note;
use User;

class ViewNoteController extends BaseController
{
    /** @var string */
    protected $tpl_name = 'view_note.tpl.html';

    /** @var int */
    private $note_id;

    /** @var  int */
    private $usr_id;

    /** @var int */
    private $issue_id;

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $get = $this->getRequest()->query;

        $this->note_id = $get->getInt('id');
    }

    /**
     * @inheritdoc
     */
    protected function canAccess()
    {
        Auth::checkAuthentication();

        $this->usr_id = Auth::getUserID();
        $this->issue_id = Note::getIssueID($this->note_id);

        if (!Access::canViewInternalNotes($this->issue_id, $this->usr_id)) {
            return false;
        }

        // FIXME: is this superfluous? Access::canViewInternalNotes does all the checks?
        $prj_id = Issue::getProjectID($this->issue_id);
        $role_id = User::getRoleByUser($this->usr_id, $prj_id);
        if ($role_id < User::ROLE_USER) {
            return false;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    protected function defaultAction()
    {
    }

    /**
     * @inheritdoc
     */
    protected function prepareTemplate()
    {
        $note = Note::getDetails($this->note_id);
        if (!$note) {
            $this->tpl->assign('note', '');

            return;
        }

        $note['message'] = $note['not_note'];

        $seq_no = Note::getNoteSequenceNumber($this->issue_id, $this->note_id);
        // TRANSLATORS: %1: note sequence number, %2: note title
        $extra_title = ev_gettext('Note #%1$s: %2$s', $seq_no, $note['not_title']);
        $this->tpl->assign(
            [
                'note' => $note,
                'issue_id' => $this->issue_id,
                'extra_title' => $extra_title,
                'recipients' => Mail_Queue::getMessageRecipients('notes', $this->note_id),
            ]
        );
        $this->setSideLinks();
    }

    /**
     * Sets the next and previous notes associated with the given issue Id
     * and the currently selected note.
     */
    private function setSideLinks()
    {
        if (!$this->issue_id) {
            return;
        }

        $sides = Note::getSideLinks($this->issue_id, $this->note_id);
        $this->tpl->assign(
            [
                'previous' => $sides['previous'],
                'next' => $sides['next'],
            ]
        );
    }
}
