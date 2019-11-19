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

use Eventum\Auth\Adapter\LdapAdapter;
use Eventum\Auth\AuthException;
use Eventum\Controller\BaseController;
use Symfony\Component\Ldap\Exception\LdapException;
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
                'ldap_auth' => $this->hasLdapAuth(),
            ]
        );
    }

    protected function canAccess(): bool
    {
        // if manage controller does not implement this
        // then give access permission.
        // probably canRoleAccess satisfied access restriction.
        return true;
    }

    private function hasLdapAuth(): bool
    {
        try {
            new LdapAdapter();

            return true;
        } catch (LdapException $e) {
            return false;
        } catch (AuthException $e) {
            return false;
        }
    }
}
