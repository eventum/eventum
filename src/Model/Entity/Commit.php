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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Eventum\Model\Repository\Traits\ToArrayTrait;
use Eventum\Scm\ScmRepository;

/**
 * @ORM\Table(name="commit")
 * @ORM\Entity(repositoryClass="Eventum\Model\Repository\CommitRepository")
 */
class Commit
{
    use ToArrayTrait;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue */
    protected $com_id;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    protected $com_scm_name;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $com_project_name;

    /**
     * @var string
     * @ORM\Column(type="string", length=40, nullable=false)
     */
    protected $com_changeset;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $com_branch;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $com_usr_id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $com_author_email;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $com_author_name;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime", nullable=false)
     */
    protected $com_commit_date;

    /**
     * @var string
     * @ORM\Column(type="text", length=16777215, nullable=true)
     */
    protected $com_message;

    /**
     * @var Issue
     */
    private $issue;

    /**
     * Bidirectional - One-To-Many (INVERSE SIDE)
     *
     * @var CommitFile[]
     * @ORM\OneToMany(targetEntity="Eventum\Model\Entity\CommitFile", mappedBy="commit", cascade={"persist", "remove"})
     */
    private $files = [];

    public function __construct()
    {
        $this->files = new ArrayCollection();
    }

    public function setId(int $id): self
    {
        $this->com_id = $id;

        return $this;
    }

    public function getId(): int
    {
        return $this->com_id;
    }

    public function setScmName(string $scmName): self
    {
        $this->com_scm_name = $scmName;

        return $this;
    }

    public function getScmName(): string
    {
        return $this->com_scm_name;
    }

    public function setProjectName(?string $projectName): self
    {
        $this->com_project_name = $projectName;

        return $this;
    }

    public function getProjectName(): ?string
    {
        return $this->com_project_name;
    }

    public function setChangeset(string $changeset): self
    {
        $this->com_changeset = $changeset;

        return $this;
    }

    public function getChangeset(bool $short = false): string
    {
        // truncate if it's longer than 16 (cvs commitid)
        if ($short && strlen($this->com_changeset) > 16) {
            return substr($this->com_changeset, 0, 7);
        }

        return $this->com_changeset;
    }

    public function setBranch(?string $branch): self
    {
        $this->com_branch = $branch;

        return $this;
    }

    public function getBranch(): ?string
    {
        return $this->com_branch;
    }

    public function setUserId(?int $usr_id): self
    {
        $this->com_usr_id = $usr_id;

        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->com_usr_id;
    }

    public function setAuthorEmail(?string $authorEmail): self
    {
        $this->com_author_email = $authorEmail;

        return $this;
    }

    public function getAuthorEmail(): ?string
    {
        return $this->com_author_email;
    }

    public function setAuthorName(string $authorName): self
    {
        $this->com_author_name = $authorName;

        return $this;
    }

    public function getAuthorName(): string
    {
        return $this->com_author_name ?: '';
    }

    public function setCommitDate(DateTime $commitDate): self
    {
        $this->com_commit_date = $commitDate;

        return $this;
    }

    public function getCommitDate(): DateTime
    {
        return $this->com_commit_date;
    }

    public function setMessage(string $message): self
    {
        $this->com_message = $message;

        return $this;
    }

    public function getMessage(): string
    {
        return $this->com_message;
    }

    public function addFile(CommitFile $cf): self
    {
        $cf->setCommit($this);

        $this->files[] = $cf;

        return $this;
    }

    /**
     * @return CommitFile[]
     */
    public function getFiles()
    {
        return $this->files;
    }

    public function getIssue(): Issue
    {
        return $this->issue;
    }

    public function setIssue(Issue $issue): self
    {
        $this->issue = $issue;

        return $this;
    }

    /**
     * Get formatted author name, combining name and email
     */
    public function getAuthor(): string
    {
        $name = $this->getAuthorName();
        $email = $this->getAuthorEmail();

        if (!$email) {
            return $name;
        }

        return "$name <$email>";
    }

    public function getCommitRepo(): ScmRepository
    {
        return new ScmRepository($this->getScmName());
    }
}
