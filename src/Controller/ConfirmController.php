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

use Eventum\Auth\AuthException;
use Eventum\Controller\Traits\NotFoundExceptionTrait;
use Eventum\Controller\Traits\RedirectResponseTrait;
use Eventum\Controller\Traits\SmartyResponseTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use User;

class ConfirmController
{
    use NotFoundExceptionTrait;
    use RedirectResponseTrait;
    use SmartyResponseTrait;

    /** @var string */
    protected $tpl_name = 'confirm.tpl.html';

    /** @var string */
    private $cat;

    public function defaultAction(Request $request): Response
    {
        $this->cat = $request->query->get('cat');
        $email = $request->query->get('email');
        $hash = $request->query->get('hash');

        if ($this->cat === 'newuser') {
            return $this->newUserAction($email, $hash);
        }

        if ($this->cat === 'password') {
            return $this->passwordAction($email, $hash);
        }

        throw $this->createNotFoundException('The route does not exist');
    }

    private function newUserAction(?string $email, ?string $hash): Response
    {
        $res = User::checkHash($email, $hash);
        if ($res == 1) {
            User::confirmVisitorAccount($email);
            // redirect user to login form with pretty message
            $this->redirect('index.php', ['err' => AuthException::ACCOUNT_ACTIVATED, 'email' => $email]);
        }

        $params = [
            'cat' => $this->cat,
            'confirm_result' => $res,
        ];

        return $this->render($this->tpl_name, $params);
    }

    private function passwordAction(?string $email, ?string $hash): Response
    {
        $params = [
            'cat' => $this->cat,
        ];

        $res = User::checkHash($email, $hash);
        if ($res == 1) {
            User::confirmNewPassword($email);
            $params['email'] = $email;
        }

        $params['confirm_result'] = $res;

        return $this->render($this->tpl_name, $params);
    }
}
