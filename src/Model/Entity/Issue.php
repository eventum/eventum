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
     * @var Commit[]
     * @ManyToMany(targetEntity="Eventum\Model\Entity\Commit")
     * @JoinTable(name="issue_commit",
     *   joinColumns={@JoinColumn(name="isc_iss_id", referencedColumnName="iss_id")},
     *   inverseJoinColumns={@JoinColumn(name="isc_com_id", referencedColumnName="com_id", unique=true)}
     * )
     */
    private $commits;

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
}
