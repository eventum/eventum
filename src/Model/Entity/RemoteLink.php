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

/**
 * @Table(name="remote_link", indexes={@Index(name="rel_id", columns={"rel_id", "rel_gid"})})
 * @Entity(repositoryClass="Eventum\Model\Repository\RemoteLinkRepository")
 */
class RemoteLink
{
    /**
     * @var int
     * @Column(name="rel_id", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var int
     * @Column(name="rel_iss_id", type="integer", nullable=false)
     */
    private $issue_id;

    /**
     * @var string
     * @Column(name="rel_gid", type="string", length=255, nullable=true)
     */
    private $gid;

    /**
     * @var string
     * @Column(name="rel_relation", type="string", length=255, nullable=false)
     */
    private $relation;

    /**
     * @var string
     * @Column(name="rel_url", type="text", length=65535, nullable=false)
     */
    private $url;

    /**
     * @var string
     * @Column(name="rel_title", type="string", length=255, nullable=false)
     */
    private $title;

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

    public function setGid(?string $gid): self
    {
        $this->gid = $gid;

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
