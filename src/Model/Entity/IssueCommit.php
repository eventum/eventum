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
 * @Entity @Table(name="issue_commit")
 */
class IssueCommit extends BaseModel
{
    /**
     * @var int
     *
     * @Id @Column(type="integer") @GeneratedValue
     */
    protected $isc_id;

    /**
     * @var int
     *
     * @Column(type="integer", nullable=false)
     */
    protected $isc_iss_id;

    /**
     * @var int
     *
     * @Column(type="integer", nullable=false)
     */
    protected $isc_com_id;

    /**
     * @param int $id
     * @return $this
     */
    public function setId($id)
    {
        $this->isc_id = $id;

        return $this;
    }

    /**
     * Get iscId
     *
     * @return int
     */
    public function getId()
    {
        return $this->isc_id;
    }

    /**
     * Set iscIssId
     *
     * @param int $issueId
     * @return IssueCommit
     */
    public function setIssueId($issueId)
    {
        $this->isc_iss_id = $issueId;

        return $this;
    }

    /**
     * Get iscIssId
     *
     * @return int
     */
    public function getIssueId()
    {
        return $this->isc_iss_id;
    }

    /**
     * Set iscComId
     *
     * @param int $commitId
     * @return IssueCommit
     */
    public function setCommitId($commitId)
    {
        $this->isc_com_id = $commitId;

        return $this;
    }

    /**
     * Get iscComId
     *
     * @return int
     */
    public function getCommitId()
    {
        return $this->isc_com_id;
    }

    /** @var Commit[] */
    private $commits;
    public function __construct()
    {
        $this->commits = [];
    }

    public function addCommits($commit)
    {
        $this->commits[] = $commit;
    }

    /**
     * @param int $issue_id
     * @return $this[]
     */
    public function findByIssueId($issue_id)
    {
        return $this->findAllByConditions(['isc_iss_id' => $issue_id]);
    }
}
