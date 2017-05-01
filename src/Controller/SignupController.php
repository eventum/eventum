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
use Eventum\Controller\Helper\MessagesHelper;
use Setup;
use User;

class SignupController extends BaseController
{
    /** @var string */
    protected $tpl_name = 'signup.tpl.html';

    /** @var string */
    private $cat;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $request = $this->getRequest();

        $this->cat = $request->request->get('cat');
    }

    /**
     * {@inheritdoc}
     */
    protected function canAccess()
    {
        // log anonymous users out so they can use the signup form
        if (AuthCookie::hasAuthCookie() && Auth::isAnonUser()) {
            Auth::logout();
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction()
    {
        if ($this->cat == 'signup') {
            $this->createVisitorAccountAction();
        }
    }

    private function createVisitorAccountAction()
    {
        $setup = Setup::get();

        if ($setup['open_signup'] != 'enabled') {
            $error = ev_gettext('Sorry, but this feature has been disabled by the administrator.');
            $this->error($error);
        }

        $res = User::createVisitorAccount($setup['accounts_role'], $setup['accounts_projects']);
        $this->tpl->assign('signup_result', $res);

        //  TODO: translate
        $map = [
            1 => ['Thank you, your account creation request was processed successfully. For security reasons a confirmation email was sent to the provided email address with instructions on how to confirm your request and activate your account.', MessagesHelper::MSG_INFO],
            -1 => ['Error: An error occurred while trying to run your query.', MessagesHelper::MSG_ERROR],
            -2 => ['Error: The email address specified is already associated with an user in the system.', MessagesHelper::MSG_ERROR],
        ];
        $this->messages->mapMessages($res, $map);
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate()
    {
    }
}
