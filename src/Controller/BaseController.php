<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2015 Eventum Team.                                     |
// |                                                                      |
// | This program is free software; you can redistribute it and/or modify |
// | it under the terms of the GNU General Public License as published by |
// | the Free Software Foundation; either version 2 of the License, or    |
// | (at your option) any later version.                                  |
// |                                                                      |
// | This program is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
// | GNU General Public License for more details.                         |
// |                                                                      |
// | You should have received a copy of the GNU General Public License    |
// | along with this program; if not, write to:                           |
// |                                                                      |
// | Free Software Foundation, Inc.                                       |
// | 51 Franklin Street, Suite 330                                        |
// | Boston, MA 02110-1301, USA.                                          |
// +----------------------------------------------------------------------+

namespace Eventum\Controller;

use Symfony\Component\HttpFoundation\Request;
use Auth;
use Misc;
use Template_Helper;

abstract class BaseController
{
    protected $tpl_name;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->tpl = new Template_Helper($this->tpl_name);

        $this->configure();
    }

    /**
     * Checks access, invokes defaultAction()
     * and if defaultAction() does not return proper value, throws an exception
     */
    public function run()
    {
        // NOTE: canAccess needs $issue_id for the template
        if (!$this->canAccess()) {
            $this->displayTemplate('permission_denied.tpl.html');
            exit;
        }

        $this->defaultAction();
        $this->prepareTemplate();
        $this->displayTemplate();
    }

    /**
     * @return Request
     */
    protected function getRequest()
    {
        static $request;
        if (!$request) {
            $request = Request::createFromGlobals();
        }
        return $request;
    }

    /**
     * display template
     */
    protected function displayTemplate($tpl_name = null)
    {
        // set new template, if needed
        if ($tpl_name) {
            $this->tpl->setTemplate($tpl_name);
        }
        $this->tpl->displayTemplate();
    }

    /**
     * Display error message $msg and exit
     *
     * @param string $msg
     */
    protected function error($msg) {
        // TODO: move Misc::displayErrorMessage contents here,
        // once this is only place it's called from
        Misc::displayErrorMessage($msg);
    }

    /**
     * Create class variables from request.
     * Creating variables that require user to be authenticated, will mostly not work.
     *
     * Use one of these to obtain data from GET/POST or both:
     * $request = $this->getRequest();
     *
     * // obtain from GET, PATH or POST
     * $this->cat = $request->get('cat');
     * // from POST
     * $this->cat = $request->request->get('cat');
     * // from GET
     * $this->cat = $request->query->get('cat');
     * // if you need POST -> GET, then do:
     * $this->cat = $request->request->get('cat') ?: $request->query->get('cat');
     */
    protected abstract function configure();

    /**
     * should return true if method can be accessed
     *
     * @return bool
     */
    protected abstract function canAccess();

    /**
     * default action of a controller
     * controller may chose sub-actions from there
     */
    protected abstract function defaultAction();

    /**
     * Setup variables needed to render a template.
     */
    protected abstract function prepareTemplate();
}
