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

use Access;
use Auth;
use AuthCookie;
use DB_Helper;
use Email_Account;
use Issue;
use Prefs;
use Setup;
use Support;

class EmailsController extends BaseController
{
    /** @var string */
    protected $tpl_name = 'emails.tpl.html';

    /** @var int */
    protected $usr_id;

    /** @var int */
    private $prj_id;

    /** @var int */
    private $default_pager_size;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $request = $this->getRequest();

        $this->prj_id = $request->query->getInt('prj_id');
        $this->default_pager_size = Setup::getDefaultPagerSize();
    }

    /**
     * {@inheritdoc}
     */
    protected function canAccess(): bool
    {
        Auth::checkAuthentication();

        $this->usr_id = Auth::getUserID();
        if (!Access::canAccessAssociateEmails($this->usr_id)) {
            // TODO: cleanup template from 'no_access'
            //$tpl->assign('no_access', 1);
            return false;
        }

        $prj_id = Auth::getCurrentProject();
        if ($this->prj_id && $this->prj_id != $prj_id) {
            AuthCookie::setProjectCookie($this->prj_id);
            // TODO: redirect and check access for project switch!
            $this->prj_id = $prj_id;
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
     * {@inheritdoc}
     */
    protected function prepareTemplate(): void
    {
        $pagerRow = Support::getParam('pagerRow') ?: 0;
        $rows = Support::getParam('rows') ?: $this->default_pager_size;

        $options = Support::saveSearchParams();
        $list = Support::getEmailListing($options, $pagerRow, $rows);
        $refreshRate = Prefs::getUserPreference($this->usr_id)->getEmailRefreshRate() * 60;

        $this->tpl->assign(
            [
                'options' => $options,
                'sorting' => $this->getSortingInfo($options),

                'list' => $list['list'],
                'list_info' => $list['info'],
                'issues' => Issue::getColList(),
                'accounts' => Email_Account::getAssocList($this->prj_id),

                'refresh_rate' => $refreshRate,
                'refresh_page' => 'emails.php',
            ]
        );
    }

    /**
     * Method used to get the current sorting options used in the grid
     * layout of the emails listing page.
     *
     * @param   array $options The current search parameters
     * @return  array The sorting options
     */
    private function getSortingInfo($options)
    {
        $uri = $this->getRequest()->getBaseUrl();

        $fields = [
            'sup_from',
            'sup_customer_id',
            'sup_date',
            'sup_to',
            'sup_iss_id',
            'sup_subject',
        ];
        $items = [
            'links' => [],
            'order' => [],
        ];

        $sort_order_option = strtolower(DB_Helper::orderBy($options['sort_order']));

        foreach ($fields as $field) {
            $sort_order = 'asc';
            if ($options['sort_by'] == $field) {
                if ($sort_order_option === 'asc') {
                    $sort_order = 'desc';
                } else {
                    $sort_order = 'asc';
                }
                $items['order'][$field] = $sort_order_option;
            }
            $items['links'][$field] = $uri . '?sort_by=' . $field . '&sort_order=' . $sort_order;
        }

        return $items;
    }
}
