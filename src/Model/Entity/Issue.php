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
 * @ORM\Entity
 * @ORM\Table(name="issue")
 * @ORM\Entity(repositoryClass="Eventum\Model\Repository\IssueRepository")
 */
class Issue
{
    /**
     * @var int
     * @ORM\Column(name="iss_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var int
     * @ORM\Column(name="iss_prj_id", type="integer", nullable=false)
     */
    private $projectId;

    /**
     * @var Status
     * @ORM\OneToOne(targetEntity="\Eventum\Model\Entity\Status")
     * @ORM\JoinColumn(name="iss_sta_id", referencedColumnName="sta_id")
     */
    private $status;

    /**
     * @var DateTime
     * @ORM\Column(name="iss_created_date", type="datetime", nullable=false)
     */
    private $createdDate;

    /**
     * @var string
     * @ORM\Column(name="iss_summary", type="string", length=128, nullable=false)
     */
    private $summary;

    /**
     * @var string
     *
     * @ORM\Column(name="iss_description", type="text", length=65535, nullable=false)
     */
    private $description;

    /**
     * @var Commit[]
     * @ORM\ManyToMany(targetEntity="Eventum\Model\Entity\Commit", cascade={"persist", "remove"})
     * @ORM\JoinTable(name="issue_commit",
     *   joinColumns={@ORM\JoinColumn(name="isc_iss_id", referencedColumnName="iss_id")},
     *   inverseJoinColumns={@ORM\JoinColumn(name="isc_com_id", referencedColumnName="com_id", unique=true)}
     * )
     */
    private $commits;

    public function __construct()
    {
        $this->createdDate = new DateTime();
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): int
    {
        return $this->id;
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

    public function setStatus(Status $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function getStatusId(): int
    {
        return $this->status->getId();
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

    public function getSummary(): string
    {
        return $this->summary;
    }

    public function setSummary(string $summary): self
    {
        $this->summary = $summary;

        return $this;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param Commit[] $commits
     * @return Issue
     */
    public function setCommits(array $commits): self
    {
        $this->commits = $commits;

        return $this;
    }

    public function addCommit(Commit $commit): self
    {
        $commit->setIssue($this);

        $this->commits[] = $commit;

        return $this;
    }

    /**
     * @return Commit[]|\Traversable
     */
    public function getCommits()
    {
        return $this->commits;
    }
}
