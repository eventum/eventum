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
use Eventum\ServiceContainer;
use News;
use Project;
use Search_Profile;
use Stats;
use User;

class MainController extends BaseController
{
    /** @var string */
    protected $tpl_name = 'main.tpl.html';

    /** @var int */
    private $usr_id;

    /** @var int */
    private $prj_id;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
    }

    /**
     * {@inheritdoc}
     */
    protected function canAccess(): bool
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
     * {@inheritdoc}
     */
    protected function defaultAction(): void
    {
    }

    /**
     * Load hide_closed flag from various sources
     * if it was set from GET/POST, update cookie and search profile
     *
     * FIXME: why both? drop cookie?
     */
    private function getHideClosedFlag(): bool
    {
        $cookie_name = ServiceContainer::getConfig()['hide_closed_stats_cookie'];
        $request = $this->getRequest();
        $hide_closed = null;

        // hide_closed is NULL if it's not specified in GET/POST
        // otherwise it's 1 or 0
        if ($request->query->has('hide_closed') || $request->request->has('hide_closed')) {
            $hide_closed = (int) $request->request->get('hide_closed') ?: (int) $request->query->get('hide_closed');

            Auth::setCookie($cookie_name, $hide_closed, time() + Date_Helper::YEAR);
            Search_Profile::save(
                $this->usr_id,
                $this->prj_id,
                'stats',
                ['hide_closed' => $hide_closed]
            );
        }

        // load it from cookie
        if ($hide_closed === null && isset($_COOKIE[$cookie_name])) {
            $hide_closed = $_COOKIE[$cookie_name];
        }

        // load it from search profile
        if ($hide_closed === null) {
            $search_profile = Search_Profile::getProfile($this->usr_id, $this->prj_id, 'stats');

            if (!empty($search_profile)) {
                $hide_closed = $search_profile['hide_closed'];
            }
        }

        return (bool) $hide_closed;
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(): void
    {
        $hide_closed = $this->getHideClosedFlag();
        $hide_stats = false;

        if ($this->role_id == User::ROLE_CUSTOMER && ($crm = CRM::getInstance($this->prj_id))) {
            // need the activity dashboard here
            $contact_id = User::getCustomerContactID($this->usr_id);
            $customer_id = Auth::getCurrentCustomerID();
            $this->tpl->assign(
                [
                    'contact' => $crm->getContact($contact_id),
                    'customer' => $crm->getCustomer($customer_id),
                ]
            );
        } else {
            if ($this->role_id <= User::ROLE_REPORTER && Project::getSegregateReporters($this->prj_id)) {
                $hide_stats = true;
            } else {
                $this->tpl->assign(
                    [
                        'status' => Stats::getStatus(),
                        'releases' => Stats::getRelease($hide_closed),
                        'categories' => Stats::getCategory($hide_closed),
                        'priorities' => Stats::getPriority($hide_closed),
                        'users' => Stats::getUser($hide_closed),
                        'emails' => Stats::getEmailStatus(),
                        'pie_chart' => true,
                    ]
                );
            }
        }

        $this->tpl->assign(
            [
                'hide_stats' => $hide_stats,
                'hide_closed' => $hide_closed,
                'news' => News::getListByProject($this->prj_id),
            ]
        );
    }
}
