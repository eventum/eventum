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
use Eventum\Controller\Traits\RedirectResponseTrait;
use Eventum\Controller\Traits\SmartyResponseTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class IndexController
{
    use RedirectResponseTrait;
    use SmartyResponseTrait;

    /** @var string */
    protected $tpl_name = 'index.tpl.html';

    public function defaultAction(Request $request, Security $security, UrlGeneratorInterface $urlGenerator): Response
    {
        // https://symfony.com/doc/current/security.html#a-configuring-how-your-users-will-authenticate
        $user = $security->getUser();
        $isUser = $user instanceof UserInterface;

        $has_valid_cookie = $isUser && AuthCookie::hasAuthCookie();
        $is_anon_user = Auth::isAnonUser();

        // log anonymous users out so they can use the login form
        if ($has_valid_cookie && $is_anon_user) {
            Auth::logout();
        }

        if ($has_valid_cookie && !$is_anon_user) {
            $params = [];
            $url = (string)$request->get('url');
            if ($url) {
                $params['url'] = $url;
            }

            return $this->redirect('select_project.php', $params);
        }

        $externalLoginUrl = Auth::getExternalLoginURL();
        if (Auth::autoRedirectToExternalLogin()) {
            return $this->redirect($externalLoginUrl, [], true);
        }

        $url = $urlGenerator->generate('login');

        return $this->redirect($url);
    }
}
