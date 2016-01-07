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

class ForgotPasswordController extends BaseController
{
    /** @var string */
    protected $tpl_name = 'forgot_password.tpl.html';

    /** @var string */
    private $cat;

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $request = $this->getRequest();

        $this->cat = $request->request->get('cat');
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
        if ($this->cat == 'reset_password') {
            $res = $this->resetPasswordAction();
            $this->tpl->assign('result', $res);
        }
    }

    private function resetPasswordAction()
    {
        $post = $this->getRequest()->request;
        $email = $post->get('email');

        if (!$email) {
            return 4;
        }

        $usr_id = User::getUserIDByEmail($email, true);
        if (!$usr_id) {
            return 5;
        }

        $info = User::getDetails($usr_id);
        if (!User::isActiveStatus($info['usr_status'])) {
            return 3;
        }

        User::sendPasswordConfirmationEmail($usr_id);

        return 1;
    }

    /**
     * @inheritdoc
     */
    protected function prepareTemplate()
    {
    }
}
