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

namespace Eventum\Security;

use Auth;
use Eventum\Auth\AuthException;
use Eventum\Controller\Traits\RedirectResponseTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Logout\LogoutHandlerInterface;

class LogoutListener implements LogoutHandlerInterface
{
    use RedirectResponseTrait;

    public function logout(Request $request, Response $response, TokenInterface $token): Response
    {
        Auth::logout();

        return $this->redirect('index.php', ['err' => AuthException::LOGGED_OUT]);
    }
}
