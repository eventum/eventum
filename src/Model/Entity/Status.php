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
 * @ORM\Table(name="status", uniqueConstraints={@ORM\UniqueConstraint(name="sta_abbreviation", columns={"sta_abbreviation"})}, indexes={@ORM\Index(name="sta_rank", columns={"sta_rank"}), @ORM\Index(name="sta_is_closed", columns={"sta_is_closed"})})
 * @ORM\Entity
 * @ORM\Entity(repositoryClass="Eventum\Model\Repository\StatusRepository")
 */
class Status
{
    /**
     * @var int
     * @ORM\Column(name="sta_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="sta_title", type="string", length=64, nullable=false)
     */
    private $title;

    /**
     * @var string
     * @ORM\Column(name="sta_abbreviation", type="string", length=3, nullable=false)
     */
    private $abbreviation;

    /**
     * @var int
     * @ORM\Column(name="sta_rank", type="integer", nullable=false)
     */
    private $rank;

    /**
     * @var string
     * @ORM\Column(name="sta_color", type="string", length=7, nullable=false)
     */
    private $color;

    /**
     * @var bool
     * @ORM\Column(name="sta_is_closed", type="boolean", nullable=false)
     */
    private $isClosed;

    public function __construct(int $id)
    {
        $this->id = $id;
    }

    public function getId(): int
    {
        return $this->id;
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

    public function setAbbreviation(string $abbreviation): self
    {
        $this->abbreviation = $abbreviation;

        return $this;
    }

    public function getAbbreviation(): string
    {
        return $this->abbreviation;
    }

    public function setRank(int $rank): self
    {
        $this->rank = $rank;

        return $this;
    }

    public function getRank(): int
    {
        return $this->rank;
    }

    public function setColor(string $color): self
    {
        $this->color = $color;

        return $this;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function setIsClosed(bool $isClosed): self
    {
        $this->isClosed = $isClosed;

        return $this;
    }

    public function isClosed(): bool
    {
        return $this->isClosed;
    }
}
