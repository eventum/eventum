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
 * @ORM\Table(name="history_type")
 * @ORM\Entity
 */
class HistoryType
{
    /**
     * @var int
     * @ORM\Column(name="htt_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="htt_name", type="string", length=25, nullable=false)
     */
    private $name;

    /**
     * @var int
     * @ORM\Column(name="htt_role", type="integer", nullable=true)
     */
    private $roleId;

    public function getId(): int
    {
        return $this->id;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setRoleId(int $roleId): self
    {
        $this->roleId = $roleId;

        return $this;
    }

    public function getRoleId(): int
    {
        return $this->roleId;
    }
}
