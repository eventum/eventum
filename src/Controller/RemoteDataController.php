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
use Draft;
use Eventum\EmailHelper;
use Issue;
use Link_Filter;
use Mail_Queue;
use Note;
use Phone_Support;
use Support;
use User;

/*
 * This page is used to return a single content to the expandable table using
 * httpClient library or jQuery.
 */

class RemoteDataController extends BaseController
{
    /** @var int */
    private $usr_id;

    /** @var int */
    private $prj_id;

    /** @var string */
    private $action;

    /** @var string */
    private $callback;

    /** @var string */
    private $list_id;

    /** @var string */
    private $ec_id;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $request = $this->getRequest();

        $this->action = (string) $request->get('action');
        $this->callback = (string) $request->query->get('callback');
        $this->list_id = (string) $request->get('list_id');
        $this->ec_id = (string) $request->get('ec_id');
    }

    /**
     * {@inheritdoc}
     */
    protected function canAccess()
    {
        Auth::checkAuthentication();

        $this->usr_id = Auth::getUserID();
        $this->prj_id = Auth::getCurrentProject();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction()
    {
        switch ($this->action) {
            case 'email':
                $res = $this->getEmail($this->list_id);
                break;

            case 'note':
                $res = $this->getNote($this->list_id);
                break;

            case 'draft':
                $res = $this->getDraft($this->list_id);
                break;

            case 'phone':
                $res = $this->getPhoneSupport($this->list_id);
                break;

            case 'mailqueue':
                $res = $this->getMailQueue($this->list_id);
                break;

            case 'description':
                $res = $this->getIssueDescription($this->list_id);
                break;

            default:
                $res = 'ERROR: Unable to call function ' . htmlspecialchars($this->action);
        }

        // convert to wanted format
        $res = [
            'ec_id' => $this->ec_id,
            'list_id' => $this->list_id,
            'message' => $res,
        ];

        if ($this->callback) {
            echo $this->callback, '(', json_encode($res), ')';
        } else {
            echo $res['message'];
        }
        exit;
    }

    /**
     * @param string $issue_id
     */
    private function getIssueDescription($issue_id)
    {
        if (!Issue::canAccess($issue_id, $this->usr_id)) {
            return null;
        }
        $details = Issue::getDetails($issue_id);

        return $this->processText($details['iss_description']);
    }

    /**
     * Selects the email from the table and returns the contents.
     *
     * @param   string $id the sup_ema_id and sup_id separated by a -
     * @return  string A string containing the body of the email,
     */
    private function getEmail($id)
    {
        list($ema_id, $sup_id) = explode('-', $id);
        $info = Support::getEmailDetails($sup_id);

        if (!Issue::canAccess($info['sup_iss_id'], $this->usr_id)) {
            return null;
        }

        if (!$this->ec_id) {
            return $info['seb_body'];
        }

        return EmailHelper::formatEmail($info['seb_body']);
    }

    /**
     * Selects a note from the table and returns the contents.
     *
     * @param   string $id the ID of this note
     * @return  string a string containing the note
     */
    private function getNote($id)
    {
        $note = Note::getDetails($id);
        if (!Issue::canAccess($note['not_iss_id'], $this->usr_id)) {
            return null;
        }

        if (!$this->ec_id) {
            return $note['not_note'];
        }

        return EmailHelper::formatEmail($note['not_note']);
    }

    /**
     * Selects a draft from the table and returns the contents.
     *
     * @param   string $id the ID of this draft
     * @return  string a string containing the note
     */
    private function getDraft($id)
    {
        $info = Draft::getDetails($id);
        if (!Issue::canAccess($info['emd_iss_id'], $this->usr_id)) {
            return null;
        }

        if (!$this->ec_id) {
            return $info['emd_body'];
        }

        return EmailHelper::formatEmail($info['emd_body']);
    }

    /**
     * Selects a phone support entry from the table and returns the contents.
     *
     * @param   string $id the phone support entry ID
     * @return  string a string containing the description
     */
    private function getPhoneSupport($id)
    {
        $res = Phone_Support::getDetails($id);
        if (!Issue::canAccess($res['phs_iss_id'], $this->usr_id)) {
            return '';
        }
        if (!$this->ec_id) {
            return $res['phs_description'];
        }

        return $this->processText(nl2br(htmlspecialchars($res['phs_description'])));
    }

    /**
     * Selects a mail queue entry from the table and returns the contents.
     *
     * @param   string $id the mail queue entry ID
     * @return  string a string containing the body
     */
    private function getMailQueue($id)
    {
        if (Auth::getCurrentRole() < User::ROLE_DEVELOPER) {
            return null;
        }

        $res = Mail_Queue::getEntry($id);
        if (!Issue::canAccess($res['maq_iss_id'], $this->usr_id)) {
            return '';
        }

        if (!$this->ec_id) {
            return $res['maq_body'];
        }

        $raw = $res['maq_headers'] . "\n" . $res['maq_body'];

        return nl2br(htmlspecialchars($raw, ENT_SUBSTITUTE));
    }

    private function processText($text)
    {
        return Link_Filter::processText($this->prj_id, $text);
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate()
    {
    }
}
