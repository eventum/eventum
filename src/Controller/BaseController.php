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
use InvalidArgumentException;
use Misc;
use Symfony\Component\HttpFoundation\Request;
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

        if (!$this->tpl_name) {
            throw new InvalidArgumentException('No template to render');
        }

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
    protected function error($msg)
    {
        // TODO: move Misc::displayErrorMessage contents here,
        // once this is only place it's called from
        Misc::displayErrorMessage($msg);
    }

    /**
     * Redirect to an url with optional GET parameters.
     * This method never returns.
     *
     * @param string $url
     * @param array $params
     */
    protected function redirect($url, $params = array())
    {
        if ($params) {
            $q = strstr($url, '?') ? '&' : '?';
            $url .= $q . http_build_query($params, null, '&');
        }

        // TODO: drop Auth::redirect once this is only place Auth::redirect is used
        Auth::redirect($url);
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
    abstract protected function configure();

    /**
     * should return true if method can be accessed
     *
     * @return bool
     */
    abstract protected function canAccess();

    /**
     * default action of a controller
     * controller may chose sub-actions from there
     */
    abstract protected function defaultAction();

    /**
     * Setup variables needed to render a template.
     */
    abstract protected function prepareTemplate();
}
