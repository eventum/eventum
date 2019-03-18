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
 * @ORM\Table(name="search_profile", uniqueConstraints={@ORM\UniqueConstraint(name="sep_usr_id", columns={"sep_usr_id", "sep_prj_id", "sep_type"})})
 * @ORM\Entity(repositoryClass="Eventum\Model\Repository\SearchProfileRepository")
 */
class SearchProfile
{
    /**
     * @var int
     * @ORM\Column(name="sep_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var int
     * @ORM\Column(name="sep_usr_id", type="integer", nullable=false)
     */
    private $userId;

    /**
     * @var int
     * @ORM\Column(name="sep_prj_id", type="integer", nullable=false)
     */
    private $projectId;

    /**
     * @var string The type of the search profile ('issue' or 'email')
     * @ORM\Column(name="sep_type", type="string", length=5, nullable=false)
     */
    private $type;

    /**
     * @var array
     * @ORM\Column(name="sep_user_profile", type="array", length=65535, nullable=false)
     */
    private $user_profile;

    public function getId(): int
    {
        return $this->id;
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

    public function setProjectId(int $projectId): self
    {
        $this->projectId = $projectId;

        return $this;
    }

    public function getProjectId(): int
    {
        return $this->projectId;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setUserProfile(array $user_profile): self
    {
        $this->user_profile = $user_profile;

        return $this;
    }

    public function getUserProfile(): array
    {
        return $this->user_profile;
    }
}
