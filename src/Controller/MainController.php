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
use CRM;
use Date_Helper;
use News;
use Project;
use Search_Profile;
use Stats;
use User;

class MainController extends BaseController
{
    /** @var string */
    protected $tpl_name = 'main.tpl.html';

    /** @var bool */
    private $hide_closed;

    /** @var int */
    private $role_id;

    /** @var int */
    private $usr_id;

    /** @var int */
    private $prj_id;

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $request = $this->getRequest();

        // hide_closed is NULL if it's not specified on GET/POST
        // otherwise it's 1 or 0
        if ($request->query->has('hide_closed') || $request->request->has('hide_closed')) {
            $this->hide_closed = $request->request->get('hide_closed') ?: $request->query->get('hide_closed');
        }
    }

    /**
     * @inheritdoc
     */
    protected function canAccess()
    {
        Auth::checkAuthentication();

        $this->prj_id = Auth::getCurrentProject();
        $this->role_id = Auth::getCurrentRole();
        $this->usr_id = Auth::getUserID();

        // redirect partners to list.php instead of sanitizing this page
        if (User::isPartner($this->usr_id)) {
            $this->redirect('list.php');
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    protected function defaultAction()
    {
    }

    /**
     * @return int
     */
    private function getHideClosedFlag()
    {
        $cookie_name = APP_HIDE_CLOSED_STATS_COOKIE;

        // update cookie from GET/POST
        if ($this->hide_closed !== null) {
            Auth::setCookie($cookie_name, $this->hide_closed, time() + Date_Helper::YEAR);
            $_COOKIE[$cookie_name] = $this->hide_closed;
        }

        if (isset($_COOKIE[$cookie_name])) {
            $hide_closed = $_COOKIE[$cookie_name];
        } else {
            $hide_closed = 0;
        }

        return $hide_closed;
    }

    /**
     * @inheritdoc
     */
    protected function prepareTemplate()
    {
        $hide_closed = $this->getHideClosedFlag();
        $hide_stats = false;

        if ($this->role_id == User::ROLE_CUSTOMER && ($crm = CRM::getInstance($this->prj_id))) {
            // need the activity dashboard here
            $contact_id = User::getCustomerContactID($this->usr_id);
            $customer_id = Auth::getCurrentCustomerID();
            $this->tpl->assign(
                array(
                    'contact' => $crm->getContact($contact_id),
                    'customer' => $crm->getCustomer($customer_id),
                )
            );
        } else {
            if ($this->role_id <= User::ROLE_REPORTER && Project::getSegregateReporters($this->prj_id)) {
                $hide_stats = true;
            } else {
                $this->tpl->assign(
                    array(
                        'status' => Stats::getStatus(),
                        'releases' => Stats::getRelease($hide_closed),
                        'categories' => Stats::getCategory($hide_closed),
                        'priorities' => Stats::getPriority($hide_closed),
                        'users' => Stats::getUser($hide_closed),
                        'emails' => Stats::getEmailStatus($hide_closed),
                        'pie_chart' => Stats::getPieChart($hide_closed),
                    )
                );
            }
        }

        if ($this->hide_closed === null) {
            $search_profile = Search_Profile::getProfile($this->usr_id, $this->prj_id, 'stats');

            if (!empty($search_profile)) {
                $hide_closed = $search_profile['hide_closed'];
            }
        } else {
            Search_Profile::save(
                $this->usr_id, $this->prj_id, 'stats', array('hide_closed' => $hide_closed)
            );
        }

        $this->tpl->assign(
            array(
                'hide_stats' => $hide_stats,
                'hide_closed' => $hide_closed,
                'news' => News::getListByProject($this->prj_id),
            )
        );
    }
}
