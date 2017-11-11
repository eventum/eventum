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
 * @Entity
 * @Table(name="issue")
 * @Entity(repositoryClass="Eventum\Model\Repository\IssueRepository")
 */
class Issue
{
    /**
     * @var int
     * @Column(name="iss_id", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var int
     * @Column(name="iss_prj_id", type="integer", nullable=false)
     */
    private $project_id;

    /**
     * @var string
     * @Column(name="iss_summary", type="string", length=128, nullable=false)
     */
    private $summary;

    /**
     * @var Commit[]
     * @ManyToMany(targetEntity="Eventum\Model\Entity\Commit", cascade={"persist", "remove"})
     * @JoinTable(name="issue_commit",
     *   joinColumns={@JoinColumn(name="isc_iss_id", referencedColumnName="iss_id")},
     *   inverseJoinColumns={@JoinColumn(name="isc_com_id", referencedColumnName="com_id", unique=true)}
     * )
     */
    private $commits;

    /**
     * @param int $id
     * @return Issue
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getSummary()
    {
        return $this->summary;
    }

    /**
     * @param string $summary
     * @return Issue
     */
    public function setSummary($summary)
    {
        $this->summary = $summary;

        return $this;
    }

    /**
     * @param Commit[] $commits
     * @return Issue
     */
    public function setCommits($commits)
    {
        $this->commits = $commits;

        return $this;
    }

    /**
     * @param Commit $commit
     * @return Issue
     */
    public function addCommit(Commit $commit)
    {
        $commit->setIssue($this);

        $this->commits[] = $commit;

        return $this;
    }

    /**
     * @return Commit[]|\Traversable
     */
    public function getCommits()
    {
        return $this->commits;
    }

    /**
     * @param int $project_id
     * @return Issue
     */
    public function setProjectId($project_id)
    {
        $this->project_id = $project_id;

        return $this;
    }

    /**
     * @return int
     */
    public function getProjectId()
    {
        return $this->project_id;
    }
}
