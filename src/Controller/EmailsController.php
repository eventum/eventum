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
use Email_Account;
use Issue;
use Prefs;
use Support;

class EmailsController extends BaseController
{
    /** @var string */
    protected $tpl_name = 'emails.tpl.html';

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

        $this->prj_id = $request->query->getInt('prj_id');
    }

    /**
     * @inheritdoc
     */
    protected function canAccess()
    {
        Auth::checkAuthentication();

        $usr_id = Auth::getUserID();
        if (!Access::canAccessAssociateEmails($usr_id)) {
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
     * @inheritdoc
     */
    protected function defaultAction()
    {
    }

    /**
     * @inheritdoc
     */
    protected function prepareTemplate()
    {
        $pagerRow = Support::getParam('pagerRow') ?: 0;
        $rows = Support::getParam('rows') ?: APP_DEFAULT_PAGER_SIZE;

        $options = Support::saveSearchParams();
        $list = Support::getEmailListing($options, $pagerRow, $rows);
        $prefs = Prefs::get($this->usr_id);

        $this->tpl->assign(
            array(
                'options' => $options,
                'sorting' => Support::getSortingInfo($options),

                'list' => $list['list'],
                'list_info' => $list['info'],
                'issues' => Issue::getColList(),
                'accounts' => Email_Account::getAssocList($this->prj_id),

                'refresh_rate' => $prefs['email_refresh_rate'] * 60,
                'refresh_page' => 'emails.php',
            )
        );
    }
}
