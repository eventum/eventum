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

namespace Eventum\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="user_project_preference")
 * @ORM\Entity
 */
class UserProjectPreference
{
    /**
     * @var int
     * @ORM\Column(name="upp_prj_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $projectId;

    /**
     * @var UserPreference
     * @ORM\ManyToOne(targetEntity="UserPreference", inversedBy="projects")
     * @ORM\JoinColumn(name="upp_usr_id", referencedColumnName="upr_usr_id", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $userPreference;

    /**
     * @var bool
     * @ORM\Column(name="upp_receive_assigned_email", type="boolean", nullable=false)
     */
    private $receiveAssignedEmail = APP_DEFAULT_ASSIGNED_EMAILS;

    /**
     * @var bool
     * @ORM\Column(name="upp_receive_new_issue_email", type="boolean", nullable=false)
     */
    private $receiveNewIssueEmail = APP_DEFAULT_NEW_EMAILS;

    /**
     * @var bool
     * @ORM\Column(name="upp_receive_copy_of_own_action", type="boolean", nullable=false)
     */
    private $receiveCopyOfOwnAction = APP_DEFAULT_COPY_OF_OWN_ACTION;

    public function __construct(UserPreference $upr, int $projectId)
    {
        $this->userPreference = $upr;
        $this->projectId = $projectId;
    }

    public function setProjectId(int $projectId): self
    {
        $this->projectId = $projectId;

        return $this;
    }

    public function getProjectId(): int
    {
        return $this->projectId;
    }

    public function setReceiveAssignedEmail(bool $value): self
    {
        $this->receiveAssignedEmail = $value;

        return $this;
    }

    public function receiveAssignedEmail(): bool
    {
        return $this->receiveAssignedEmail;
    }

    public function setReceiveNewIssueEmail(bool $value): self
    {
        $this->receiveNewIssueEmail = $value;

        return $this;
    }

    public function receiveNewIssueEmail(): bool
    {
        return $this->receiveNewIssueEmail;
    }

    public function setReceiveCopyOfOwnAction(bool $value): self
    {
        $this->receiveCopyOfOwnAction = $value;

        return $this;
    }

    public function receiveCopyOfOwnAction(): bool
    {
        return $this->receiveCopyOfOwnAction;
    }
}
