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

namespace Eventum\Controller\Ajax;

use Auth;
use Date_Helper;
use Issue;
use User;

class UpdateController extends AjaxBaseController
{
    /** @var int */
    private $usr_id;
    /** @var int */
    private $prj_id;
    /** @var int */
    private $issue_id;
    /** @var string */
    private $field_name;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $request = $this->getRequest();

        $this->issue_id = $request->request->getInt('issue_id');
        $this->field_name = $request->request->filter('field_name', null, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    }

    /**
     * {@inheritdoc}
     */
    protected function canAccess(): bool
    {
        Auth::checkAuthentication();

        if (!$this->issue_id || !Issue::exists($this->issue_id)) {
            $this->error('Invalid issue_id');
        }

        $this->usr_id = Auth::getUserID();
        $this->prj_id = Issue::getProjectID($this->issue_id);

        if (User::getRoleByUser($this->usr_id, $this->prj_id) < User::ROLE_USER) {
            return false;
        }

        if (!Issue::canAccess($this->issue_id, $this->usr_id)) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function ajaxAction(): void
    {
        switch ($this->field_name) {
            case 'expected_resolution_date':
                $this->updateExpectedResolutionDate();
                break;

            default:
                $this->error("Object type '$this->field_name' not supported");
                break;
        }
    }

    private function updateExpectedResolutionDate(): void
    {
        $post = $this->getRequest()->request;

        $day = $post->getInt('day');
        $month = $post->getInt('month');
        $year = $post->getInt('year');

        if ($day === 0 && $month === 1 && $year === 0) {
            // clear button
            $date = null;
        } else {
            $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
        }

        $res = Issue::setExpectedResolutionDate($this->issue_id, $date);
        if ($res === -1) {
            $this->error('Update failed');
        }

        if ($date !== null) {
            echo Date_Helper::getSimpleDate($date, false);
        }
    }
}
