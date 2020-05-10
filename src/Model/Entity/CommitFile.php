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
use Eventum\Model\Repository\Traits\ToArrayTrait;

/**
 * @ORM\Table(name="commit_file")
 * @ORM\Entity(repositoryClass="Eventum\Model\Repository\CommitFileRepository")
 */
class CommitFile
{
    use ToArrayTrait;

    /**
     * @var int
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    protected $cof_id;

    /**
     * Bidirectional - Many Comments are authored by one user (OWNING SIDE)
     *
     * @var Commit
     * @ORM\ManyToOne(targetEntity="Eventum\Model\Entity\Commit", inversedBy="files")
     * @ORM\JoinColumn(nullable=false, name="cof_com_id", referencedColumnName="com_id")
     */
    private $commit;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    protected $cof_filename;

    /**
     * @var bool The flag whether file was added
     * @ORM\Column(type="boolean", nullable=false)
     */
    protected $cof_added = false;

    /**
     * @var bool The flag whether file was modified
     * @ORM\Column(type="boolean", nullable=false)
     */
    protected $cof_modified = false;

    /**
     * @var bool The flag whether file was removed
     * @ORM\Column(type="boolean", nullable=false)
     */
    protected $cof_removed = false;

    /**
     * @var string
     * @ORM\Column(type="string", length=40, nullable=true)
     */
    protected $cof_old_version;

    /**
     * @var string
     * @ORM\Column(name="cof_new_version", type="string", length=40, nullable=true)
     */
    protected $cof_new_version;

    public function setId(int $id): self
    {
        $this->cof_id = $id;

        return $this;
    }

    public function getId(): int
    {
        return $this->cof_id;
    }

    public function setCommit(Commit $commit): self
    {
        $this->commit = $commit;

        return $this;
    }

    public function getCommit(): Commit
    {
        return $this->commit;
    }

    public function setFilename(string $filename): self
    {
        $this->cof_filename = $filename;

        return $this;
    }

    public function getFilename(): string
    {
        return $this->cof_filename;
    }

    public function setAdded(bool $added): self
    {
        $this->cof_added = $added;

        return $this;
    }

    public function isAdded(): bool
    {
        return $this->cof_added;
    }

    public function setModified(bool $modified): self
    {
        $this->cof_modified = $modified;

        return $this;
    }

    public function isModified(): bool
    {
        return $this->cof_modified;
    }

    public function setRemoved(bool $removed): self
    {
        $this->cof_removed = $removed;

        return $this;
    }

    public function isRemoved(): bool
    {
        return $this->cof_removed;
    }

    public function setOldVersion(?string $oldVersion): self
    {
        $this->cof_old_version = $oldVersion;

        return $this;
    }

    public function getOldVersion(): ?string
    {
        return $this->cof_old_version;
    }

    public function setNewVersion(?string $newVersion): self
    {
        $this->cof_new_version = $newVersion;

        return $this;
    }

    public function getNewVersion(): ?string
    {
        return $this->cof_new_version;
    }

    /**
     * Indicate whether file has versions
     */
    public function hasVersions(): bool
    {
        return $this->getOldVersion() or $this->getNewVersion();
    }
}
