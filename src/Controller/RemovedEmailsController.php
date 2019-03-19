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
use Support;

class RemovedEmailsController extends BaseController
{
    /** @var string */
    protected $tpl_name = 'removed_emails.tpl.html';

    /** @var string */
    private $cat;

    /** @var array */
    private $items;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $request = $this->getRequest();

        $this->cat = $request->request->get('cat');
        $this->items = $request->request->get('item');
    }

    /**
     * {@inheritdoc}
     */
    protected function canAccess(): bool
    {
        Auth::checkAuthentication(null, true);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction(): void
    {
        switch ($this->cat) {
            case 'restore':
                $res = Support::restoreEmails();
                $this->tpl->assign('result_msg', $res);
                break;

            case 'remove':
                $res = Support::expungeEmails($this->items);
                $this->tpl->assign('result_msg', $res);
                break;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(): void
    {
        $this->tpl->assign('list', Support::getRemovedList());
    }
}
