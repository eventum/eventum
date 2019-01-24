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
 * RemoteLinks
 *
 * @ORM\Table(name="remote_links", indexes={@ORM\Index(name="rel_id", columns={"rel_id", "rel_gid"})})
 * @ORM\Entity
 */
class RemoteLinks
{
    /**
     * @var int
     * @ORM\Column(name="rel_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="rel_gid", type="string", length=255, nullable=true)
     */
    private $gid;

    /**
     * @var string
     * @ORM\Column(name="rel_relationship", type="string", length=255, nullable=false)
     */
    private $relationship;

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

    public function getId(): int
    {
        return $this->id;
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

    public function setRelationship(string $relationship): self
    {
        $this->relationship = $relationship;

        return $this;
    }

    public function getRelationship(): string
    {
        return $this->relationship;
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
