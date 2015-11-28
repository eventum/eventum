<?php

namespace Eventum\Controller;

use Auth;
use Validation;

class LoginController extends BaseController
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
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
        $post = $this->getRequest()->request;

        $login = (string)$post->get('email');
        if (Validation::isWhitespace($login)) {
            $this->redirect('index.php?err=1');
        }

        $passwd = (string)$post->get('passwd');
        if (Validation::isWhitespace($passwd)) {
            Auth::saveLoginAttempt($login, 'failure', 'empty password');
            $this->redirect('index.php?err=2', array('email' => $login));
        }

        // check if user exists
        if (!Auth::userExists($login)) {
            Auth::saveLoginAttempt($login, 'failure', 'unknown user');
            $this->redirect('index.php?err=3');
        }

        // check if user is locked
        $usr_id = Auth::getUserIDByLogin($login);
        if (Auth::isUserBackOffLocked($usr_id)) {
            Auth::saveLoginAttempt($login, 'failure', 'account back-off locked');
            $this->redirect('index.php?err=13');
        }

        // check if the password matches
        if (!Auth::isCorrectPassword($login, $passwd)) {
            Auth::saveLoginAttempt($login, 'failure', 'wrong password');
            $this->redirect('index.php?err=3', array('email' => $login));
        }

        Auth::login($login);

        $params = array();
        if ($url = $post->get('url')) {
            $params['url'] = (string)$url;
        }

        $this->redirect('select_project.php', $params);
    }

    /**
     * @inheritdoc
     */
    protected function prepareTemplate()
    {
    }
}
