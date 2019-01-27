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
use Eventum\Auth\AuthException;
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
     * {@inheritdoc}
     */
    protected function configure()
    {
        $post = $this->getRequest()->request;

        $this->login = (string)$post->get('email');
        $this->passwd = (string)$post->get('passwd');
        $this->url = (string)$post->get('url');
    }

    /**
     * {@inheritdoc}
     */
    protected function canAccess()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction()
    {
        try {
            $this->login($this->login, $this->passwd);
        } catch (AuthException $e) {
            $this->loginFailure($e->getCode(), $e->getMessage(), ['login' => $this->login]);
        }

        $params = [];
        if ($this->url) {
            $params['url'] = $this->url;
        }

        $this->redirect('select_project.php', $params);
    }

    private function login(string $login, string $passwd)
    {
        if (Validation::isWhitespace($login)) {
            throw new AuthException('empty login', AuthException::EMPTY_LOGIN);
        }

        if (Validation::isWhitespace($passwd)) {
            throw new AuthException('empty password', AuthException::EMPTY_PASSWORD);
        }

        if (!Auth::userExists($login)) {
            throw new AuthException('unknown user', AuthException::UNKNOWN_USER);
        }

        if (!Auth::isCorrectPassword($login, $passwd)) {
            throw new AuthException('wrong password', AuthException::WRONG_PASSWORD);
        }

        Auth::login($login);
    }

    /**
     * Log login failure and redirect to login form
     *
     * @param int $error
     * @param string $reason
     * @param array $params
     */
    private function loginFailure($error, $reason, $params = [])
    {
        Auth::saveLoginAttempt($this->login, 'failure', $reason);

        $params['err'] = $error;
        $this->redirect('index.php', $params);
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate()
    {
    }
}
