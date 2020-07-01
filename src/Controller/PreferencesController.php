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
use Eventum\Db\Doctrine;
use Eventum\Model\Entity\UserPreference;
use Eventum\Model\Repository\UserPreferenceRepository;
use Exception;
use Language;
use Project;
use Symfony\Component\HttpFoundation\ParameterBag;
use Throwable;
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
    /** @var array */
    private $permissions;
    /** @var UserPreferenceRepository */
    private $repo;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $post = $this->getRequest()->request;

        $this->cat = $post->get('cat');
        $this->lang = $post->get('language');
        $this->repo = Doctrine::getUserPreferenceRepository();
    }

    /**
     * {@inheritdoc}
     */
    protected function canAccess(): bool
    {
        Auth::checkAuthentication();

        if (Auth::isAnonUser()) {
            $this->redirect('index.php');
        }

        $this->usr_id = Auth::getUserID();
        $this->role_id = Auth::getCurrentRole();
        $this->permissions = $this->getPermissions();

        if ($this->isPostRequest()) {
            $hasAccess = $this->permissions[$this->cat] ?? null;
            if (!$hasAccess) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction(): void
    {
        $res = null;

        switch ($this->cat) {
            case 'update_account':
                $res = $this->updateAccountAction();
                break;

            case 'update_project':
                $res = $this->updateProjectAction();
                break;

            case 'update_name':
                $res = $this->updateNameAction();
                break;

            case 'update_email':
                $res = $this->updateEmailAction();
                break;

            case 'update_sms':
                $res = $this->updateSmsAction();
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

    private function updateProjectAction(): int
    {
        try {
            $post = $this->getRequest()->request;
            $projects = $post->get('projects') ?: [];
            $this->repo->updateProjectPreference($this->usr_id, $projects);

            $res = 1;
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);

            $res = -1;
        }

        if ($this->lang) {
            User::setLang($this->usr_id, $this->lang);
        }

        return $res;
    }

    private function updateAccountAction(): int
    {
        try {
            $post = $this->getRequest()->request;

            // if the user is trying to upload a new signature, override any changes to the textarea
            if (!empty($_FILES['file_signature']['name'])) {
                $contents = file_get_contents($_FILES['file_signature']['tmp_name']);
                $post->set('email_signature', $contents);
            }

            $prefs = $this->repo->findOrCreate($this->usr_id);
            $this->updateFromRequest($prefs, $post);
            $this->repo->persistAndFlush($prefs);

            $res = 1;
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);

            $res = -1;
        }

        if ($this->lang) {
            User::setLang($this->usr_id, $this->lang);
        }

        return $res;
    }

    private function updatePasswordAction(): int
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
            $this->logger->error($e);

            return -1;
        }
    }

    private function regenerateApiTokenAction(): ?int
    {
        $res = APIAuthToken::regenerateKey($this->usr_id);
        if ($res == 1) {
            // FIXME: looks like hack, return error string instead
            $this->messages->addInfoMessage(ev_gettext('Your key has been regenerated. All previous keys are now invalid.'));
            $res = null;
        }

        return $res;
    }

    protected function updateNameAction(): int
    {
        return User::updateFullName($this->usr_id);
    }

    protected function updateEmailAction(): int
    {
        return User::updateEmail($this->usr_id);
    }

    protected function updateSmsAction(): int
    {
        $post = $this->getRequest()->request;
        $res = User::updateSMS($this->usr_id, $post->get('sms_email'));

        return $res ? 1 : 0;
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(): void
    {
        $upr = $this->repo->findOrCreate($this->usr_id);
        $projects = Project::getAssocList($this->usr_id, false, true);

        $this->tpl->assign(
            [
                'timezone' => $upr->getTimezone(),
                'relative_date' => $this->html->radioYesNoButtons($upr->useRelativeDate()),
                'collapsed_emails' => $this->html->radioYesNoButtons($upr->collapsedEmails()),
                'close_popup_windows' => $this->html->radioYesNoButtons($upr->autoClosePopupWindow()),
                'receive_new_issue_email' => $this->html->radioYesNoButtons($upr->autoClosePopupWindow()),
                'auto_append_email_sig' => $upr->autoAppendNoteSignature(),
                'auto_append_note_sig' => $upr->autoAppendNoteSignature(),
                'week_firstday' => $upr->getWeekFirstday(),
                'list_refresh_rate' => $upr->getListRefreshRate(),
                'email_refresh_rate' => $upr->getEmailRefreshRate(),
                'email_signature' => $upr->getEmailSignature(),
                'projects' => $this->getProjectPreferences($upr, $projects),
                'sms_email' => User::getSMS($this->usr_id),
                'user_info' => User::getDetails($this->usr_id),
                'assigned_projects' => $projects,
                'zones' => Date_Helper::getTimezoneList(),
                'avail_langs' => Language::getAvailableLanguages(),
                'current_locale' => User::getLang($this->usr_id, true),
                'permissions' => $this->permissions,
            ]
        );

        if ($this->permissions['regenerate_token']) {
            $this->tpl->assign('api_tokens', APIAuthToken::getTokensForUser($this->usr_id, false, true));
        }
    }

    private function getProjectPreferences(UserPreference $upr, array $projects): array
    {
        $result = [];
        foreach ($projects as $prj_id => $project) {
            $upp = $upr->findOrCreateProjectById($prj_id);
            $result[$upp->getProjectId()] = [
                'receive_new_issue_email' => $this->html->radioYesNoButtons($upp->receiveNewIssueEmail()),
                'receive_assigned_email' => $this->html->radioYesNoButtons($upp->receiveAssignedEmail()),
                'receive_copy_of_own_action' => $this->html->radioYesNoButtons($upp->receiveCopyOfOwnAction()),
            ];
        }

        return $result;
    }

    private function getPermissions(): array
    {
        $notCustomer = $this->role_id !== User::ROLE_CUSTOMER;
        $isCustomer = $this->role_id >= User::ROLE_CUSTOMER;
        $isUser = $this->role_id >= User::ROLE_USER;
        $isViewer = $this->role_id >= User::ROLE_VIEWER;

        return [
            'update_account' => $isViewer,
            'update_name' => $notCustomer && Auth::canUserUpdateName($this->usr_id),
            'update_email' => $notCustomer && Auth::canUserUpdateEmail($this->usr_id),
            'update_sms' => $isCustomer,
            'update_project' => $isCustomer,
            'update_password' => Auth::canUserUpdatePassword($this->usr_id),
            'regenerate_token' => $isUser,
        ];
    }

    private function updateFromRequest(UserPreference $prefs, ParameterBag $post): UserPreference
    {
        return $prefs
            ->setTimezone($post->get('timezone'))
            ->setWeekFirstday($post->getInt('week_firstday'))
            ->setListRefreshRate($post->getInt('list_refresh_rate'))
            ->setEmailRefreshRate($post->getInt('email_refresh_rate'))
            ->setEmailSignature($post->get('email_signature'))
            ->setAutoClosePopupWindow($post->getBoolean('close_popup_windows'))
            ->setRelativeDate($post->getBoolean('relative_date'))
            ->setCollapsedEmails($post->getBoolean('collapsed_emails'))
            ->setAutoAppendEmailSignature($post->getBoolean('auto_append_email_sig'))
            ->setAutoAppendNoteSignature($post->getBoolean('auto_append_note_sig'));
    }
}
