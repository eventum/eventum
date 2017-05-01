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

use APIAuthToken;
use Auth;
use Date_Helper;
use Eventum\Monolog\Logger;
use Exception;
use Language;
use Prefs;
use Project;
use User;

class PreferencesController extends BaseController
{
    /** @var string */
    protected $tpl_name = 'preferences.tpl.html';

    /** @var int */
    private $usr_id;

    /** @var string */
    private $cat;

    /** @var string */
    private $lang;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $post = $this->getRequest()->request;

        $this->cat = $post->get('cat');
        $this->lang = $post->get('language');
    }

    /**
     * {@inheritdoc}
     */
    protected function canAccess()
    {
        Auth::checkAuthentication();

        if (Auth::isAnonUser()) {
            $this->redirect('index.php');
        }

        $this->usr_id = Auth::getUserID();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction()
    {
        $res = null;

        switch ($this->cat) {
            case 'update_account':
                $res = $this->updateAccountAction();
                break;

            case 'update_name':
                $res = User::updateFullName($this->usr_id);
                break;

            case 'update_email':
                $res = User::updateEmail($this->usr_id);
                break;

            case 'update_password':
                $res = $this->updatePasswordAction();
                break;

            case 'regenerate_token':
                $res = $this->regenerateApiTokenAction();
                break;
        }

        if ($res == 1) {
            $this->messages->addInfoMessage(ev_gettext('Your information has been updated'));
        } elseif ($res !== null) {
            $this->messages->addErrorMessage(ev_gettext('Sorry, there was an error updating your information'));
        }

        // redirect back to preferences because ui language may have changed
        // and also to avoid reload page repost
        if ($this->isPostRequest()) {
            $this->redirect('preferences.php');
        }
    }

    private function updateAccountAction()
    {
        $preferences = $this->getRequest()->request->all();

        // if the user is trying to upload a new signature, override any changes to the textarea
        if (!empty($_FILES['file_signature']['name'])) {
            $preferences['email_signature'] = file_get_contents($_FILES['file_signature']['tmp_name']);
        }

        // XXX: $res only updated for Prefs::set
        $res = Prefs::set($this->usr_id, $preferences);

        User::updateSMS($this->usr_id, $preferences['sms_email']);

        if ($this->lang) {
            User::setLang($this->usr_id, $this->lang);
        }

        return $res;
    }

    private function updatePasswordAction()
    {
        $post = $this->getRequest()->request;
        $password = $post->get('password');

        // verify current password
        if (!Auth::isCorrectPassword(Auth::getUserLogin(), $password)) {
            $this->messages->addErrorMessage(ev_gettext('Incorrect password'));

            return -3;
        }

        $new_password = $post->get('new_password');
        $confirm_password = $post->get('confirm_password');

        if ($new_password != $confirm_password) {
            $this->messages->addErrorMessage(ev_gettext('New passwords mismatch'));

            return -2;
        }

        if ($password == $new_password) {
            $this->messages->addErrorMessage(ev_gettext('Please set different password than current'));

            return -2;
        }

        try {
            User::updatePassword($this->usr_id, $new_password);

            return 1;
        } catch (Exception $e) {
            Logger::app()->error($e);

            return -1;
        }
    }

    private function regenerateApiTokenAction()
    {
        $res = APIAuthToken::regenerateKey($this->usr_id);
        if ($res == 1) {
            // FIXME: looks like hack, return error string instead
            $this->messages->addInfoMessage(ev_gettext('Your key has been regenerated. All previous keys are now invalid.'));
            $res = null;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate()
    {
        $prefs = Prefs::get($this->usr_id);
        $prefs['sms_email'] = User::getSMS($this->usr_id);

        $this->tpl->assign([
                'user_prefs' => $prefs,
                'user_info' => User::getDetails($this->usr_id),
                'assigned_projects' => Project::getAssocList($this->usr_id, false, true),
                'zones' => Date_Helper::getTimezoneList(),
                'avail_langs' => Language::getAvailableLanguages(),
                'current_locale' => User::getLang($this->usr_id, true),

                'can_update_name' => Auth::canUserUpdateName($this->usr_id),
                'can_update_email' => Auth::canUserUpdateEmail($this->usr_id),
                'can_update_password' => Auth::canUserUpdatePassword($this->usr_id),
            ]
        );

        if (Auth::getCurrentRole() >= User::ROLE_USER) {
            $this->tpl->assign('api_tokens', APIAuthToken::getTokensForUser($this->usr_id, false, true));
        }
    }
}
