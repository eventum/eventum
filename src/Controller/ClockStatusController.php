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
use Setup;
use User;

class ClockStatusController extends BaseController
{
    /** @var string */
    private $url;

    /** @var int */
    private $usr_id;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $request = $this->getRequest();

        $this->url = $request->get('current_page');
    }

    /**
     * {@inheritdoc}
     */
    protected function canAccess(): bool
    {
        Auth::checkAuthentication(null, true);

        $this->usr_id = Auth::getUserID();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction(): void
    {
        if (User::isClockedIn($this->usr_id)) {
            User::clockOut($this->usr_id);
            $message = ev_gettext('You have been clocked out');
        } else {
            User::clockIn($this->usr_id);
            $message = ev_gettext('You have been clocked in');
        }

        $this->messages->addInfoMessage($message);

        $url = $this->url ?: Setup::getRelativeUrl() . 'list.php';
        $this->redirect($url);
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(): void
    {
    }
}
