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
use CRM;
use InvalidArgumentException;
use Issue;
use Language;
use Project;
use User;

class SelectProjectController extends BaseController
{
    /** @var string */
    protected $tpl_name = 'select_project.tpl.html';

    /** @var string */
    private $cat;

    /** @var string */
    private $url;

    /** @var string */
    private $err;

    /** @var int */
    private $usr_id;

    /** @var int */
    private $prj_id;

    /** @var bool */
    private $remember;

    /** @var array */
    private $projects;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $request = $this->getRequest();

        $this->err = $request->query->get('err');
        $this->cat = $request->request->get('cat');
        $this->url = $request->request->get('url') ?: $request->query->get('url');
        $this->prj_id = $request->request->get('project');
        $this->remember = $request->request->get('remember') ? true : null;
    }

    /**
     * {@inheritdoc}
     */
    protected function canAccess()
    {
        // check if cookies are enabled, first of all
        if (!AuthCookie::hasCookieSupport()) {
            $this->redirect('index.php?err=11');
        }

        if (!AuthCookie::hasAuthCookie()) {
            $this->redirect('index.php?err=5');
        }

        $this->usr_id = Auth::getUserID();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction()
    {
        if ($this->err) {
            AuthCookie::removeProjectCookie();
            $this->tpl->assign('err', $this->err);

            return;
        }

        $this->projects = Project::getAssocList($this->usr_id);

        // MARIADB-CSTM: Remove hidden projects
        $this->projects = \MariaDB_Helper::removeHiddenProjects($this->usr_id, $this->projects);

        // FIXME: why here? investigate deb5dbe6
        Language::setPreference();

        try {
            $prj_id = $this->getProjectId();
        } catch (InvalidArgumentException $e) {
            $this->tpl->assign('err', 1);

            return;
        }

        if (!$prj_id) {
            // display template so user can select project
            return;
        }

        AuthCookie::setProjectCookie($prj_id, $this->remember);
        $this->checkCustomerAuthentication($prj_id);
        $url = $this->url ?: 'list.php';
        $this->redirect($url);
    }

    /**
     * Try to obtain project id from various sources
     *
     * @return int|null
     */
    private function getProjectId()
    {
        // from project_cookie
        $cookie = AuthCookie::getProjectCookie();
        if ($cookie['remember'] && $cookie['prj_id']) {
            return $cookie['prj_id'];
        }

        // choose project if the list of active projects consists of just one project
        if (count($this->projects) == 1) {
            list($prj_id) = each($this->projects);

            return $prj_id;
        }

        // try from URL
        $prj_id = $this->getProjectFromUrl($this->url);

        // try from GET/POST
        if (!$prj_id && $this->prj_id) {
            $prj_id = $this->prj_id;
        }

        if (!$prj_id) {
            return null;
        }

        // validate if user has access to the project
        if (!isset($this->projects[$prj_id])) {
            throw new InvalidArgumentException();
        }

        return $prj_id;
    }

    /**
     * check if url is directly linking to an issue, and if it is, don't prompt for project
     * @param string $url
     */
    private function getProjectFromUrl($url)
    {
        if (!$url) {
            return null;
        }

        if (preg_match("/.*view\.php\?id=(\d+)/", $url, $matches)) {
            return Issue::getProjectID($matches[1]);
        }

        if (preg_match("/switch_prj_id=(\d+)/", $url, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    /**
     * @param int $prj_id
     */
    private function checkCustomerAuthentication($prj_id)
    {
        $crm = CRM::getInstance($prj_id);
        if (!$crm) {
            return;
        }

        // check if customer is expired
        $usr_role = User::getRoleByUser($this->usr_id, $prj_id);
        if ($usr_role == User::ROLE_CUSTOMER) {
            $crm->authenticateCustomer();
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate()
    {
        $this->tpl->assign('active_projects', $this->projects);
    }
}
