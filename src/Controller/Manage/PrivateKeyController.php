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
use Exception;
use User;

class PrivateKeyController extends ManageBaseController
{
    /** @var string */
    protected $tpl_name = 'manage/private_key.tpl.html';

    /** @var int */
    protected $min_role = User::ROLE_ADMINISTRATOR;

    /** @var string */
    private $cat;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $request = $this->getRequest();

        $this->cat = $request->request->get('cat') ?: $request->query->get('cat');
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction(): void
    {
        if ($this->cat == 'update') {
            $this->updateAction();
        }
    }

    private function updateAction(): void
    {
        try {
            Auth::generatePrivateKey();
            $this->messages->addInfoMessage(ev_gettext('Thank you, the private key was regenerated.'));
        } catch (Exception $e) {
            $this->messages->addErrorMessage(ev_gettext('Private key regeneration error. Check server error logs.'));
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(): void
    {
    }
}
