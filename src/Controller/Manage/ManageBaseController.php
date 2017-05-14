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

namespace Eventum\Controller\Manage;

use Eventum\Controller\BaseController;
use User;

abstract class ManageBaseController extends BaseController
{
    /** @var int */
    protected $min_role = User::ROLE_MANAGER;

    public function __construct()
    {
        parent::__construct();

        $this->tpl->assign(
            [
                'auth_backend' => strtolower(APP_AUTH_BACKEND),
            ]
        );
    }

    protected function canAccess()
    {
        // if manage controller does not implement this
        // then give access permission.
        // probably canRoleAccess satisfied access restriction.
        return true;
    }
}
