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

use User;

class ConfirmController extends BaseController
{
    /** @var string */
    protected $tpl_name = 'confirm.tpl.html';

    /** @var string */
    private $cat;

    /** @var string */
    private $email;

    /** @var string */
    private $hash;

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $request = $this->getRequest();

        $this->cat = $request->query->get('cat');
        $this->email = $request->query->get('email');
        $this->hash = $request->query->get('hash');
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
        if ($this->cat == 'newuser') {
            $this->newUserAction();
        } elseif ($this->cat == 'password') {
            $this->passwordAction();
        }
    }

    private function newUserAction()
    {
        $res = User::checkHash($this->email, $this->hash);
        if ($res == 1) {
            User::confirmVisitorAccount($this->email);
            // redirect user to login form with pretty message
            $this->redirect('index.php', ['err' => 8, 'email' => $this->email]);
        }

        $this->tpl->assign('confirm_result', $res);
    }

    private function passwordAction()
    {
        $res = User::checkHash($this->email, $this->hash);
        if ($res == 1) {
            User::confirmNewPassword($this->email);
            $this->tpl->assign('email', $this->email);
        }

        $this->tpl->assign('confirm_result', $res);
    }

    /**
     * @inheritdoc
     */
    protected function prepareTemplate()
    {
    }
}
