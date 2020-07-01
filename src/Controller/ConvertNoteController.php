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
use Note;
use User;

class ConvertNoteController extends BaseController
{
    /** @var string */
    protected $tpl_name = 'convert_note.tpl.html';

    /** @var int */
    private $issue_id;

    /** @var int */
    private $note_id;

    /** @var string */
    private $cat;

    /** @var int */
    private $usr_id;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $request = $this->getRequest();

        $this->note_id = $request->query->getInt('id') ?: $request->request->getInt('note_id');
        $this->cat = $request->request->get('cat');
    }

    /**
     * {@inheritdoc}
     */
    protected function canAccess(): bool
    {
        Auth::checkAuthentication(null, true);

        $this->usr_id = Auth::getUserID();

        $note = Note::getDetails($this->note_id);
        $this->issue_id = $note['not_iss_id'];

        $prj_id = Issue::getProjectID($this->issue_id);
        $role_id = User::getRoleByUser($this->usr_id, $prj_id);
        if ($role_id < User::ROLE_USER || !Access::canConvertNote($this->issue_id, $this->usr_id)) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction(): void
    {
        switch ($this->cat) {
            case 'convert':
                $this->convertNoteAction();
                break;
        }
    }

    private function convertNoteAction(): void
    {
        $post = $this->getRequest()->request;

        $authorize_sender = $post->get('add_authorized_replier') == 1;
        $res = Note::convertNote($post->get('note_id'), $post->get('target'), $authorize_sender);

        $this->tpl->assign(
            'convert_result',
            $res
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(): void
    {
        $this->tpl->assign(
            [
                'issue_id' => $this->issue_id,
                'note_id' => $this->note_id,
            ]
        );
    }
}
