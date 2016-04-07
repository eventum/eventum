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
    private $id;

    /**
     * @var integer
     */
    private $issueId;

    /**
     * @var integer
     */
    private $commitId;

    /**
     * Get iscId
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set iscIssId
     *
     * @param integer $issueId
     * @return IssueCommit
     */
    public function setIssueId($issueId)
    {
        $this->issueId = $issueId;

        return $this;
    }

    /**
     * Get iscIssId
     *
     * @return integer
     */
    public function getIssueId()
    {
        return $this->issueId;
    }

    /**
     * Set iscComId
     *
     * @param integer $commitId
     * @return IssueCommit
     */
    public function setCommitId($commitId)
    {
        $this->commitId = $commitId;

        return $this;
    }

    /**
     * Get iscComId
     *
     * @return integer
     */
    public function getCommitId()
    {
        return $this->commitId;
    }
}