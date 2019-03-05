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
     * @ORM\Column(name="upp_usr_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $userId;

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
     * @ORM\JoinColumn(name="upp_usr_id", referencedColumnName="upr_usr_id")
     */
    private $userPreference;

    /**
     * @var bool
     * @ORM\Column(name="upp_receive_assigned_email", type="boolean", nullable=false)
     */
    private $receiveAssignedEmail;

    /**
     * @var bool
     * @ORM\Column(name="upp_receive_new_issue_email", type="boolean", nullable=false)
     */
    private $receiveNewIssueEmail;

    /**
     * @var bool
     * @ORM\Column(name="upp_receive_copy_of_own_action", type="boolean", nullable=false)
     */
    private $receiveCopyOfOwnAction;

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
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
