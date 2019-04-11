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
use Eventum\Controller\Traits\RedirectResponseTrait;
use Symfony\Component\HttpFoundation\Response;

class LogoutController
{
    use RedirectResponseTrait;

    public function defaultAction(): Response
    {
        Auth::logout();

        return $this->redirect('index.php', ['err' => AuthException::LOGGED_OUT]);
    }
}
