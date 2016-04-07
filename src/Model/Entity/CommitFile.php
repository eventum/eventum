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
class CommitFile
{
    /**
     * @var integer
     */
    private $cofId;

    /**
     * @var integer
     */
    private $cofComId;

    /**
     * @var string
     */
    private $cofProjectName;

    /**
     * @var string
     */
    private $cofFilename;

    /**
     * @var string
     */
    private $cofOldVersion;

    /**
     * @var string
     */
    private $cofNewVersion;

    /**
     * Get cofId
     *
     * @return integer
     */
    public function getCofId()
    {
        return $this->cofId;
    }

    /**
     * Set cofComId
     *
     * @param integer $cofComId
     * @return CommitFile
     */
    public function setCofComId($cofComId)
    {
        $this->cofComId = $cofComId;

        return $this;
    }

    /**
     * Get cofComId
     *
     * @return integer
     */
    public function getCofComId()
    {
        return $this->cofComId;
    }

    /**
     * Set cofProjectName
     *
     * @param string $cofProjectName
     * @return CommitFile
     */
    public function setCofProjectName($cofProjectName)
    {
        $this->cofProjectName = $cofProjectName;

        return $this;
    }

    /**
     * Get cofProjectName
     *
     * @return string
     */
    public function getCofProjectName()
    {
        return $this->cofProjectName;
    }

    /**
     * Set cofFilename
     *
     * @param string $cofFilename
     * @return CommitFile
     */
    public function setCofFilename($cofFilename)
    {
        $this->cofFilename = $cofFilename;

        return $this;
    }

    /**
     * Get cofFilename
     *
     * @return string
     */
    public function getCofFilename()
    {
        return $this->cofFilename;
    }

    /**
     * Set cofOldVersion
     *
     * @param string $cofOldVersion
     * @return CommitFile
     */
    public function setCofOldVersion($cofOldVersion)
    {
        $this->cofOldVersion = $cofOldVersion;

        return $this;
    }

    /**
     * Get cofOldVersion
     *
     * @return string
     */
    public function getCofOldVersion()
    {
        return $this->cofOldVersion;
    }

    /**
     * Set cofNewVersion
     *
     * @param string $cofNewVersion
     * @return CommitFile
     */
    public function setCofNewVersion($cofNewVersion)
    {
        $this->cofNewVersion = $cofNewVersion;

        return $this;
    }

    /**
     * Get cofNewVersion
     *
     * @return string
     */
    public function getCofNewVersion()
    {
        return $this->cofNewVersion;
    }
}