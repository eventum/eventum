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
use Misc;
use User;

class ClockStatusController extends BaseController
{
    /** @var string */
    private $url;

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $request = $this->getRequest();

        $this->url = $request->get('current_page');
    }

    /**
     * @inheritdoc
     */
    protected function canAccess()
    {
        Auth::checkAuthentication(null, true);

        $this->usr_id = Auth::getUserID();

        return true;
    }

    /**
     * @inheritdoc
     */
    protected function defaultAction()
    {
        if (User::isClockedIn($this->usr_id)) {
            User::ClockOut($this->usr_id);
            $message = ev_gettext('You have been clocked out');
        } else {
            User::ClockIn($this->usr_id);
            $message = ev_gettext('You have been clocked in');
        }

        Misc::setMessage($message, Misc::MSG_INFO);

        $url = $this->url ?: APP_RELATIVE_URL . 'list.php';
        $this->redirect($url);
    }

    /**
     * @inheritdoc
     */
    protected function prepareTemplate()
    {
    }
}
