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

use Auth;
use Eventum\Controller\BaseController;
use User;

abstract class ManageBaseController extends BaseController
{
    /** @var int */
    protected $min_role = User::ROLE_MANAGER;

    /** @var int */
    protected $role_id;

    /**
     * @inheritdoc
     */
    protected function canAccess()
    {
        if ($this->is_popup) {
            Auth::checkAuthentication(null, true);
        } else {
            Auth::checkAuthentication();
        }

        $this->role_id = Auth::getCurrentRole();
        if ($this->role_id < $this->min_role) {
            if ($this->is_popup) {
                return false;
            }
            $this->error(ev_gettext('Sorry, you are not allowed to access this page.'));
        }

        return true;
    }
}
