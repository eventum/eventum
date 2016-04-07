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
 * IssueCommit
 */
class IssueCommit extends BaseModel
{
    /**
     * @var integer
     */
    private $iscId;

    /**
     * @var integer
     */
    private $iscIssId;

    /**
     * @var integer
     */
    private $iscComId;

    /**
     * Get iscId
     *
     * @return integer
     */
    public function getIscId()
    {
        return $this->iscId;
    }

    /**
     * Set iscIssId
     *
     * @param integer $iscIssId
     * @return IssueCommit
     */
    public function setIscIssId($iscIssId)
    {
        $this->iscIssId = $iscIssId;

        return $this;
    }

    /**
     * Get iscIssId
     *
     * @return integer
     */
    public function getIscIssId()
    {
        return $this->iscIssId;
    }

    /**
     * Set iscComId
     *
     * @param integer $iscComId
     * @return IssueCommit
     */
    public function setIscComId($iscComId)
    {
        $this->iscComId = $iscComId;

        return $this;
    }

    /**
     * Get iscComId
     *
     * @return integer
     */
    public function getIscComId()
    {
        return $this->iscComId;
    }
}