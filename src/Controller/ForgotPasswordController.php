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

use Eventum\Controller\Traits\SmartyResponseTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use User;

class ForgotPasswordController
{
    use SmartyResponseTrait;

    /** @var string */
    protected $tpl_name = 'forgot_password.tpl.html';

    public function defaultAction(Request $request): Response
    {
        $params = [];
        $cat = $request->request->get('cat');

        if ($cat === 'reset_password') {
            $params['result'] = $this->resetPasswordAction($request->request->get('email'));
        }

        return $this->render($this->tpl_name, $params);
    }

    private function resetPasswordAction(?string $email): int
    {
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
}
