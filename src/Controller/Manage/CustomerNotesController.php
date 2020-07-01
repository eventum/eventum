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

namespace Eventum\Controller\Manage;

use CRM;
use Eventum\Controller\Helper\MessagesHelper;
use Project;

class CustomerNotesController extends ManageBaseController
{
    /** @var string */
    protected $tpl_name = 'manage/customer_notes.tpl.html';

    /** @var string */
    private $cat;

    /** @var int */
    private $prj_id;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $request = $this->getRequest();

        $this->cat = $request->request->get('cat') ?: $request->query->get('cat');
        $this->prj_id = $request->request->getInt('prj_id') ?: $request->query->getInt('prj_id');
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction(): void
    {
        if ($this->cat == 'new') {
            $this->newNoteAction();
        } elseif ($this->cat == 'update') {
            $this->updateNoteAction();
        } elseif ($this->cat == 'delete') {
            $this->deleteNoteAction();
        } elseif ($this->prj_id) {
            $this->tpl->assign(
                [
                    'info' => ['cno_prj_id' => $this->prj_id],
                    'customers' => CRM::getInstance($this->prj_id)->getCustomerAssocList(),
                ]
            );
        }

        if ($this->cat == 'edit') {
            $info = CRM::getNoteDetailsByID($_GET['id']);
            if ($this->prj_id) {
                $info['cno_prj_id'] = $this->prj_id;
            }
            $this->tpl->assign(
                [
                    'customers' => CRM::getInstance($info['cno_prj_id'])->getCustomerAssocList(),
                    'info' => $info,
                ]
            );
        }
    }

    private function newNoteAction(): void
    {
        $post = $this->getRequest()->request;

        $res = CRM::insertNote($post->get('project'), $post->get('customer'), $post->get('note'));
        $map = [
            1 => [ev_gettext('Thank you, the note was added successfully.'), MessagesHelper::MSG_INFO],
            -1 => [ev_gettext('An error occurred while trying to add the new note.'), MessagesHelper::MSG_ERROR],
        ];
        $this->messages->mapMessages($res, $map);
    }

    private function updateNoteAction(): void
    {
        $post = $this->getRequest()->request;

        $res = CRM::updateNote($post->get('id'), $post->get('project'), $post->get('customer'), $post->get('note'));
        $this->messages->mapMessages(
            $res,
            [
                1 => [ev_gettext('Thank you, the note was updated successfully.'), MessagesHelper::MSG_INFO],
                -1 => [ev_gettext('An error occurred while trying to update the note.'), MessagesHelper::MSG_ERROR],
            ]
        );
    }

    private function deleteNoteAction(): void
    {
        $post = $this->getRequest()->request;

        $res = CRM::removeNotes($post->get('items'));
        $map = [
            1 => [ev_gettext('Thank you, the note was deleted successfully.'), MessagesHelper::MSG_INFO],
            -1 => [ev_gettext('An error occurred while trying to delete the note.'), MessagesHelper::MSG_ERROR],
        ];
        $this->messages->mapMessages($res, $map);
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(): void
    {
        $this->tpl->assign(
            [
                'list' => CRM::getNoteList(),
                'project_list' => Project::getAll(false),
            ]
        );
    }
}
