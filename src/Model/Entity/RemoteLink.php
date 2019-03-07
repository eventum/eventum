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
 * @ORM\Table(name="remote_link", indexes={@ORM\Index(name="rel_id", columns={"rel_id", "rel_gid"})})
 * @ORM\Entity(repositoryClass="Eventum\Model\Repository\RemoteLinkRepository")
 */
class RemoteLink
{
    /**
     * @var int
     * @ORM\Column(name="rel_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var int
     * @ORM\Column(name="rel_iss_id", type="integer", nullable=false)
     */
    private $issue_id;

    /**
     * @var DateTime
     * @ORM\Column(name="rel_created_date", type="datetime", nullable=false)
     */
    private $createdDate;

    /**
     * @var DateTime
     * @ORM\Column(name="rel_updated_date", type="datetime", nullable=false)
     */
    private $updatedDate;

    /**
     * @var string
     * @ORM\Column(name="rel_gid", type="string", length=255, nullable=true)
     */
    private $gid;

    /**
     * @var string
     * @ORM\Column(name="rel_relation", type="string", length=255, nullable=false)
     */
    private $relation;

    /**
     * @var string
     * @ORM\Column(name="rel_url", type="text", length=65535, nullable=false)
     */
    private $url;

    /**
     * @var string
     * @ORM\Column(name="rel_title", type="string", length=255, nullable=false)
     */
    private $title;

    public function __construct(int $issue_id, ?string $gid = null)
    {
        $this->setIssueId($issue_id);
        $this->setGid($gid);
        $this->setCreatedDate(new DateTime());
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setIssueId(int $issue_id): self
    {
        $this->issue_id = $issue_id;

        return $this;
    }

    public function getIssueId(): int
    {
        return $this->issue_id;
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

    public function setUpdatedDate(DateTime $updatedDate): self
    {
        $this->updatedDate = $updatedDate;

        return $this;
    }

    public function getUpdatedDate(): DateTime
    {
        return $this->updatedDate;
    }

    public function setGid(?string $gid): self
    {
        // empty gid means null
        $this->gid = $gid ?: null;

        return $this;
    }

    public function getGid(): ?string
    {
        return $this->gid;
    }

    public function setRelation(string $relation): self
    {
        $this->relation = $relation;

        return $this;
    }

    public function getRelation(): string
    {
        return $this->relation;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }
}
