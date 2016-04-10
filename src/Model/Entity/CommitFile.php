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
 * CommitFile
 */
class CommitFile extends BaseModel
{
    /**
     * @var integer
     */
    protected $cof_id;

    /**
     * @var integer
     */
    protected $cof_com_id;

    /**
     * @var string
     */
    protected $cof_filename;

    /**
     * @var string
     */
    protected $cof_old_version;

    /**
     * @var string
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
     * @return integer
     */
    public function getId()
    {
        return $this->cof_id;
    }

    /**
     * Set cofComId
     *
     * @param integer $commitId
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
     * @return integer
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
     * @param int $cid
     * @return $this[]
     */
    public function findByCommitId($cid)
    {
        return $this->findAllByConditions(array('cof_com_id' => $cid));
    }
}
