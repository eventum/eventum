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
use Validation;

class LoginController extends BaseController
{
    /** @var string */
    private $login;
    /** @var string */
    private $passwd;

    /** @var string */
    private $url;

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $post = $this->getRequest()->request;

        $this->login = (string) $post->get('email');
        $this->passwd = (string) $post->get('passwd');

        $this->url = (string) $post->get('url');
    }

    /**
     * @inheritdoc
     */
    protected function canAccess()
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    protected function defaultAction()
    {
        if (Validation::isWhitespace($this->login)) {
            $this->redirect('index.php?err=1');
        }

        if (Validation::isWhitespace($this->passwd)) {
            $this->loginFailure(2, 'empty password', array('email' => $this->login));
        }

        // check if user exists
        if (!Auth::userExists($this->login)) {
            $this->loginFailure(3, 'unknown user');
        }

        // check if user is locked
        $usr_id = Auth::getUserIDByLogin($this->login);
        if (Auth::isUserBackOffLocked($usr_id)) {
            $this->loginFailure(13, 'account back-off locked');
        }

        // check if the password matches
        if (!Auth::isCorrectPassword($this->login, $this->passwd)) {
            $this->loginFailure(3, 'wrong password', array('email' => $this->login));
        }

        Auth::login($this->login);

        $params = array();
        if ($this->url) {
            $params['url'] = $this->url;
        }

        $this->redirect('select_project.php', $params);
    }

    /**
     * Log login failure and redirect to login form
     *
     * @param int $error
     * @param string $reason
     * @param array $params
     */
    private function loginFailure($error, $reason, $params = array())
    {
        Auth::saveLoginAttempt($this->login, 'failure', $reason);

        $params['err'] = $error;
        $this->redirect('index.php', $params);
    }

    /**
     * @inheritdoc
     */
    protected function prepareTemplate()
    {
    }
}
