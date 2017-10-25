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

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Table(name="issue_commit")
 * @Entity(repositoryClass="Eventum\Model\Repository\IssueCommitRepository")
 */
class IssueCommit
{
    /**
     * @var int
     * @Id @Column(type="integer") @GeneratedValue
     */
    protected $isc_id;

    /**
     * @var int
     * @Column(type="integer", nullable=false)
     */
    protected $isc_iss_id;

    /**
     * @var int
     * @Column(type="integer", nullable=false)
     */
    protected $isc_com_id;

    /**
     * Bidirectional - One-To-Many (INVERSE SIDE)
     *
     * @var Commit[]
     * @OneToMany(targetEntity="Eventum\Model\Entity\Commit", mappedBy="commit")
     */
    /**
     * Bidirectional - Many Comments are authored by one user (OWNING SIDE)
     *
     * @var Commit[]
     * @ManyToOne(targetEntity="Eventum\Model\Entity\Commit", inversedBy="files")
     * @JoinColumn(nullable=false, name="isc_com_id", referencedColumnName="com_id")
     */
    private $commits;

    public function __construct()
    {
        $this->commits = new ArrayCollection();
    }

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

    public function addCommits(Commit $commit)
    {
        $this->commits[] = $commit;
    }

    /**
     * @return Commit[]
     */
    public function getCommits()
    {
        return $this->commits;
    }
}
