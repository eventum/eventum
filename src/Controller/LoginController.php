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
use AuthCookie;
use Eventum\Auth\Adapter\AdapterInterface;
use Eventum\Auth\AuthException;
use Eventum\Session;
use User;
use Validation;

class LoginController extends BaseController
{
    /** @var string */
    private $login;
    /** @var string */
    private $passwd;
    /** @var string */
    private $url;
    /** @var bool */
    private $remember;
    /** @var AdapterInterface */
    private $auth;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $post = $this->getRequest()->request;

        $this->login = (string)$post->get('email');
        $this->passwd = (string)$post->get('passwd');
        $this->remember = (bool)$post->get('remember');
        $this->url = (string)$post->get('url');
    }

    /**
     * {@inheritdoc}
     */
    protected function canAccess(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction(): void
    {
        $this->auth = Auth::getAuthBackend();
        try {
            $this->authenticate($this->login, $this->passwd);
            $this->login($this->login, $this->remember);
        } catch (AuthException $e) {
            $this->loginFailure($e->getCode(), $e->getMessage(), ['login' => $this->login]);
        }

        $params = [];
        if ($this->url) {
            $params['url'] = $this->url;
        }

        $this->redirect('select_project.php', $params);
    }

    private function authenticate(string $login, string $passwd): void
    {
        if (Validation::isWhitespace($login)) {
            throw new AuthException('empty login', AuthException::EMPTY_LOGIN);
        }

        if (Validation::isWhitespace($passwd)) {
            throw new AuthException('empty password', AuthException::EMPTY_PASSWORD);
        }

        if (!$this->auth->userExists($login)) {
            throw new AuthException('unknown user', AuthException::UNKNOWN_USER);
        }

        if (!$this->auth->verifyPassword($login, $passwd)) {
            throw new AuthException('wrong password', AuthException::WRONG_PASSWORD);
        }
    }

    /**
     * @see \Eventum\Security\LoginFormAuthenticator::onAuthenticationSuccess
     */
    private function login(string $login, bool $remember): void
    {
        // get user primary mail,
        // handle aliases since the user is now authenticated
        $usr_id = Auth::getUserIDByLogin($login);
        $login = User::getEmail($usr_id);

        // check if this user did already confirm his account
        if (Auth::isPendingUser($login)) {
            throw new AuthException('pending user', AuthException::PENDING_USER);
        }

        if (!Auth::isActiveUser($login)) {
            throw new AuthException('inactive user', AuthException::INACTIVE_USER);
        }

        Auth::saveLoginAttempt($login, 'success');
        AuthCookie::setAuthCookie($login, $remember);
        Session::init($usr_id);
    }

    /**
     * Log login failure and redirect to login form
     *
     * @param int $error
     * @param string $reason
     * @param array $params
     */
    private function loginFailure($error, $reason, $params = []): void
    {
        Auth::saveLoginAttempt($this->login, 'failure', $reason);

        $params['err'] = $error;
        $this->redirect('index.php', $params);
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(): void
    {
    }
}
