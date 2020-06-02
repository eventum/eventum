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
    public function __construct(?int $prj_id, ?int $issue_id, ?int $usr_id, $arguments = [], $subject = null)
    {
        parent::__construct($subject, $arguments);
        $this->setArgument('prj_id', $prj_id);
        $this->setArgument('issue_id', $issue_id);
        $this->setArgument('usr_id', $usr_id);
    }

    public function getProjectId(): ?int
    {
        return $this->getArgument('prj_id');
    }

    public function getIssueId(): ?int
    {
        return $this->getArgument('issue_id');
    }

    public function getUserId(): ?int
    {
        return $this->getArgument('usr_id');
    }
}
