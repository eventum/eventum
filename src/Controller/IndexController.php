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
use Misc;
use Project;

class IndexController extends BaseController
{
    /** @var string */
    protected $tpl_name = 'index.tpl.html';

    /** @var string */
    private $url;

    public function __construct()
    {
        // do this before creating template engine
        // TODO: move this to BaseController?
        $this->checkRequirements();
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $request = $this->getRequest();

        $this->url = (string) $request->get('url');
    }

    /**
     * {@inheritdoc}
     */
    protected function canAccess()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction()
    {
        $has_valid_cookie = AuthCookie::hasAuthCookie();
        $is_anon_user = Auth::isAnonUser();

        // log anonymous users out so they can use the login form
        if ($has_valid_cookie && $is_anon_user) {
            Auth::logout();
        }

        if ($has_valid_cookie && !$is_anon_user) {
            $params = [];
            if ($this->url) {
                $params['url'] = $this->url;
            }
            $this->redirect('select_project.php', $params);
        }

        if (Auth::autoRedirectToExternalLogin()) {
            $this->redirect(Auth::getExternalLoginURL(), [], true);
        }
    }

    private function checkRequirements()
    {
        $errors = [];

        // check if templates_c is writable by the web server user
        if (!Misc::isWritableDirectory($dir = APP_TPL_COMPILE_PATH)) {
            $errors[] = ev_gettext('Directory "%1$s" is not writable.', $dir);

            Misc::displayRequirementErrors($errors);
            exit;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate()
    {
        $projects = Project::getAnonymousList();
        $anonymous_post = (int) !empty($projects);

        $this->tpl->assign(
            [
                'anonymous_post' => $anonymous_post,
                'login_url' => Auth::getExternalLoginURL(),
            ]
        );
    }
}
