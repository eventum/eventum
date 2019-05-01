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
use Eventum\Controller\Traits\SmartyResponseTrait;
use Project;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController
{
    use SmartyResponseTrait;

    /** @var string */
    protected $tpl_name = 'index.tpl.html';

    public function login(Request $request, AuthenticationUtils $authenticationUtils)
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        $externalLoginUrl = Auth::getExternalLoginURL();

        $params = [
            'error' => $error,
            'last_user_name' => $lastUsername,
            'anonymous_post' => count(Project::getAnonymousList()) > 0,
            'login_url' => $externalLoginUrl,
        ];

        return $this->render($this->tpl_name, $params);
    }
}
