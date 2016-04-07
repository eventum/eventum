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
    private $id;

    /**
     * @var integer
     */
    private $commitId;

    /**
     * @var string
     */
    private $projectName;

    /**
     * @var string
     */
    private $filename;

    /**
     * @var string
     */
    private $oldVersion;

    /**
     * @var string
     */
    private $newVersion;

    /**
     * Get cofId
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set cofComId
     *
     * @param integer $commitId
     * @return CommitFile
     */
    public function setCommitId($commitId)
    {
        $this->commitId = $commitId;

        return $this;
    }

    /**
     * Get cofComId
     *
     * @return integer
     */
    public function getCommitId()
    {
        return $this->commitId;
    }

    /**
     * Set cofProjectName
     *
     * @param string $projectName
     * @return CommitFile
     */
    public function setProjectName($projectName)
    {
        $this->projectName = $projectName;

        return $this;
    }

    /**
     * Get cofProjectName
     *
     * @return string
     */
    public function getProjectName()
    {
        return $this->projectName;
    }

    /**
     * Set cofFilename
     *
     * @param string $filename
     * @return CommitFile
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * Get cofFilename
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Set cofOldVersion
     *
     * @param string $oldVersion
     * @return CommitFile
     */
    public function setOldVersion($oldVersion)
    {
        $this->oldVersion = $oldVersion;

        return $this;
    }

    /**
     * Get cofOldVersion
     *
     * @return string
     */
    public function getOldVersion()
    {
        return $this->oldVersion;
    }

    /**
     * Set cofNewVersion
     *
     * @param string $newVersion
     * @return CommitFile
     */
    public function setNewVersion($newVersion)
    {
        $this->newVersion = $newVersion;

        return $this;
    }

    /**
     * Get cofNewVersion
     *
     * @return string
     */
    public function getNewVersion()
    {
        return $this->newVersion;
    }
}