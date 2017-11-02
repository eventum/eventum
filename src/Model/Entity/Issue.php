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
 * @Table(name="issue", indexes={
 *     @Index(name="iss_prj_id", columns={"iss_prj_id"}),
 *     @Index(name="iss_prc_id", columns={"iss_prc_id"}),
 *     @Index(name="iss_res_id", columns={"iss_res_id"}),
 *     @Index(name="iss_grp_id", columns={"iss_grp_id"}),
 *     @Index(name="iss_duplicated_iss_id", columns={"iss_duplicated_iss_id"}),
 *     @Index(name="ft_issue", columns={"iss_summary", "iss_description"})
 * })
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
    public function addCommit($commit)
    {
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
