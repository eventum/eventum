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
use Category;
use Custom_Field;
use Filter;
use Priority;
use Product;
use Project;
use Release;
use Setup;
use Severity;
use Status;
use User;

class AdvSearchController extends BaseController
{
    /** @var string */
    protected $tpl_name = 'adv_search.tpl.html';

    /** @var int */
    protected $usr_id;

    /** @var int */
    protected $prj_id;

    /** @var int */
    private $custom_id;

    /** @var int */
    private $default_pager_size;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $request = $this->getRequest();

        $this->custom_id = $request->query->getInt('custom_id');
        $this->default_pager_size = Setup::getDefaultPagerSize();
    }

    /**
     * {@inheritdoc}
     */
    protected function canAccess(): bool
    {
        Auth::checkAuthentication();

        $this->usr_id = Auth::getUserID();
        $this->role_id = Auth::getCurrentRole();

        // customers should not be able to see this page
        if ($this->role_id == User::ROLE_CUSTOMER) {
            $this->redirect('list.php');
        }

        $this->prj_id = Auth::getCurrentProject();

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
        $users = Project::getUserAssocList($this->prj_id, 'active', User::ROLE_CUSTOMER);
        $assign_options = $this->assign->getAssignOptions($users);

        $this->tpl->assign([
            'cats' => Category::getAssocList($this->prj_id),
            'priorities' => Priority::getAssocList($this->prj_id),
            'severities' => Severity::getAssocList($this->prj_id),
            'status' => Status::getAssocStatusList($this->prj_id),
            'users' => $assign_options,
            'releases' => Release::getAssocList($this->prj_id, true),
            'custom' => Filter::getListing($this->prj_id),
            'custom_fields' => Custom_Field::getListByProject($this->prj_id, ''),
            'reporters' => Project::getReporters($this->prj_id),
            'products' => Product::getAssocList(false),
        ]);

        if ($this->custom_id) {
            $check_perm = true;
            if (Filter::isGlobal($this->custom_id)) {
                if ($this->role_id >= User::ROLE_MANAGER) {
                    $check_perm = false;
                }
            }
            $options = Filter::getDetails($this->custom_id, $check_perm);
        } else {
            $options = [];
            $options['cst_rows'] = $this->default_pager_size;
        }

        $this->tpl->assign('options', $options);
    }
}
