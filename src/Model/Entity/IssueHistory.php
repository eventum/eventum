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

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="issue_history")
 * @ORM\Entity(repositoryClass="Eventum\Model\Repository\IssueHistoryRepository")
 */
class IssueHistory
{
    /**
     * @var int
     * @ORM\Column(name="his_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var int
     * @ORM\Column(name="his_iss_id", type="integer", nullable=false)
     */
    private $issueId;

    /**
     * @var int
     * @ORM\Column(name="his_usr_id", type="integer", nullable=false)
     */
    private $userId;

    /**
     * @var int
     * @ORM\Column(name="his_htt_id", type="integer", nullable=false)
     */
    private $typeId;

    /**
     * @var bool
     * @ORM\Column(name="his_is_hidden", type="boolean", nullable=false)
     */
    private $hidden;

    /**
     * @var DateTime
     * @ORM\Column(name="his_created_date", type="datetime", nullable=false)
     */
    private $createdDate;

    /**
     * @var string
     * @ORM\Column(name="his_summary", type="text", length=16777215, nullable=false)
     */
    private $summary;

    /**
     * @var string
     * @ORM\Column(name="his_context", type="text", length=16777215, nullable=false)
     */
    private $context;

    /**
     * @var int
     * @ORM\Column(name="his_min_role", type="integer", nullable=false)
     */
    private $minRole;

    public function getId(): int
    {
        return $this->id;
    }

    public function setIssueId(int $issueId): self
    {
        $this->issueId = $issueId;

        return $this;
    }

    public function getIssueId(): int
    {
        return $this->issueId;
    }

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setTypeId(int $typeId): self
    {
        $this->typeId = $typeId;

        return $this;
    }

    public function getTypeId(): int
    {
        return $this->typeId;
    }

    public function setHidden(bool $hidden): self
    {
        $this->hidden = $hidden;

        return $this;
    }

    public function getHidden(): bool
    {
        return $this->hidden;
    }

    public function setCreatedDate(DateTime $createdDate): self
    {
        $this->createdDate = $createdDate;

        return $this;
    }

    public function getCreatedDate(): DateTime
    {
        return $this->createdDate;
    }

    public function setSummary(string $summary): self
    {
        $this->summary = $summary;

        return $this;
    }

    public function getSummary(): string
    {
        return $this->summary;
    }

    /**
     * @param string $context
     * @return IssueHistory
     */
    public function setContext(string $context): self
    {
        $this->context = $context;

        return $this;
    }

    public function getContext(): string
    {
        return $this->context;
    }

    public function setMinRole(int $minRole): self
    {
        $this->minRole = $minRole;

        return $this;
    }

    public function getMinRole(): int
    {
        return $this->minRole;
    }
}
