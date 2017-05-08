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
 * @Entity @Table(name="commit_file")
 */
class CommitFile extends BaseModel
{
    /**
     * @var int
     *
     * @Id @Column(type="integer") @GeneratedValue
     */
    protected $cof_id;

    /**
     * @var int
     *
     * @Column(type="integer", nullable=false)
     */
    protected $cof_com_id;

    /**
     * @var string
     *
     * @Column(type="string", length=255, nullable=false)
     */
    protected $cof_filename;

    /**
     * @var bool
     *
     * @Column(type="boolean", nullable=false)
     */
    protected $cof_added = false;

    /**
     * @var bool
     *
     * @Column(type="boolean", nullable=false)
     */
    protected $cof_modified = false;

    /**
     * @var bool
     *
     * @Column(type="boolean", nullable=false)
     */
    protected $cof_removed = false;

    /**
     * @var string
     *
     * @Column(type="string", length=40, nullable=true)
     */
    protected $cof_old_version;

    /**
     * @var string
     *
     * @Column(name="cof_new_version", type="string", length=40, nullable=true)
     */
    protected $cof_new_version;

    /**
     * @param int $id
     * @return $this
     */
    public function setId($id)
    {
        $this->cof_id = $id;

        return $this;
    }

    /**
     * Get cofId
     *
     * @return int
     */
    public function getId()
    {
        return $this->cof_id;
    }

    /**
     * Set cofComId
     *
     * @param int $commitId
     * @return CommitFile
     */
    public function setCommitId($commitId)
    {
        $this->cof_com_id = $commitId;

        return $this;
    }

    /**
     * Get cofComId
     *
     * @return int
     */
    public function getCommitId()
    {
        return $this->cof_com_id;
    }

    /**
     * Set cofFilename
     *
     * @param string $filename
     * @return CommitFile
     */
    public function setFilename($filename)
    {
        $this->cof_filename = $filename;

        return $this;
    }

    /**
     * Get cofFilename
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->cof_filename;
    }

    /**
     * Set boolean whether file was added
     *
     * @param bool $added
     * @return CommitFile
     */
    public function setAdded($added)
    {
        $this->cof_added = $added;

        return $this;
    }

    /**
     * Get flag whether file was added
     *
     * @return bool
     */
    public function isAdded()
    {
        return $this->cof_added;
    }

    /**
     * Set boolean whether file was modified
     *
     * @param bool $modified
     * @return CommitFile
     */
    public function setModified($modified)
    {
        $this->cof_modified = $modified;

        return $this;
    }

    /**
     * Get flag whether file was modified
     *
     * @return bool
     */
    public function isModified()
    {
        return $this->cof_modified;
    }

    /**
     * Set boolean whether file was removed
     *
     * @param bool $removed
     * @return CommitFile
     */
    public function setRemoved($removed)
    {
        $this->cof_removed = $removed;

        return $this;
    }

    /**
     * Get flag whether file was removed
     *
     * @return bool
     */
    public function isRemoved()
    {
        return $this->cof_removed;
    }

    /**
     * Set cofOldVersion
     *
     * @param string $oldVersion
     * @return CommitFile
     */
    public function setOldVersion($oldVersion)
    {
        $this->cof_old_version = $oldVersion;

        return $this;
    }

    /**
     * Get cofOldVersion
     *
     * @return string
     */
    public function getOldVersion()
    {
        return $this->cof_old_version;
    }

    /**
     * Set cofNewVersion
     *
     * @param string $newVersion
     * @return CommitFile
     */
    public function setNewVersion($newVersion)
    {
        $this->cof_new_version = $newVersion;

        return $this;
    }

    /**
     * Get cofNewVersion
     *
     * @return string
     */
    public function getNewVersion()
    {
        return $this->cof_new_version;
    }

    /**
     * Indicate whether file has versions
     */
    public function hasVersions()
    {
        return $this->getOldVersion() or $this->getNewVersion();
    }

    /**
     * @param int $cid
     * @return $this[]
     */
    public function findByCommitId($cid)
    {
        return $this->findAllByConditions(['cof_com_id' => $cid]);
    }
}
