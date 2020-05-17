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

namespace Eventum\Event;

use Symfony\Component\EventDispatcher\GenericEvent;

class EventContext extends GenericEvent
{
    /** @var int */
    public $prj_id;
    /** @var int */
    public $issue_id;
    /** @var int */
    public $usr_id;

    public function __construct(int $prj_id, int $issue_id, int $usr_id, $arguments = [], $subject = null)
    {
        parent::__construct($subject, $arguments);
        $this->setArgument('prj_id', $this->prj_id = $prj_id);
        $this->setArgument('issue_id', $this->issue_id = $issue_id);
        $this->setArgument('usr_id', $this->usr_id = $usr_id);
    }

    public function getProjectId(): int
    {
        return $this->prj_id;
    }

    public function getIssueId(): int
    {
        return $this->issue_id;
    }

    public function getUserId(): int
    {
        return $this->usr_id;
    }
}
