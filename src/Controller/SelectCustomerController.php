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
use User;

class SelectCustomerController extends BaseController
{
    /** @var string */
    protected $tpl_name = 'select_customer.tpl.html';

    /** @var int */
    private $customer_id;

    /** @var string */
    private $url;

    /** @var int */
    private $usr_id;

    /** @var int */
    private $prj_id;

    /** @var int */
    private $contact_id;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $request = $this->getRequest();

        $this->customer_id = $request->get('customer_id');
        $this->url = $request->get('url');
    }

    /**
     * {@inheritdoc}
     */
    protected function canAccess(): bool
    {
        // FIXME: why not just default Auth::checkAuthentication() here?

        // check if cookies are enabled, first of all
        if (!AuthCookie::hasCookieSupport()) {
            $this->redirect('index.php?err=11');
        }

        if (!AuthCookie::hasAuthCookie()) {
            $this->redirect('index.php?err=5');
        }

        $this->prj_id = Auth::getCurrentProject();
        $this->usr_id = Auth::getUserID();
        $this->contact_id = User::getCustomerContactID($this->usr_id);

        if (!CRM::hasCustomerIntegration($this->prj_id) || !$this->contact_id) {
            $this->redirect('main.php');
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction(): void
    {
        $crm = CRM::getInstance($this->prj_id);
        $contact = $crm->getContact($this->contact_id);
        $customers = $contact->getCustomers();

        if ($this->customer_id) {
            if (array_key_exists($this->customer_id, $customers)) {
                Auth::setCurrentCustomerID($this->customer_id);
                $this->redirect($this->url ?: 'main.php');
            }
        }

        $this->tpl->assign('customers', $customers);
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(): void
    {
    }
}
