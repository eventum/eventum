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
 * IssueAssociation
 *
 * @Table(name="issue_association", indexes={@Index(name="isa_issue_id", columns={"isa_issue_id", "isa_associated_id"})})
 * @Entity(repositoryClass="Eventum\Model\Repository\IssueAssociationRepository")
 */
class IssueAssociation
{
    /**
     * @var int
     * @Column(name="isa_id", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $isa_id;

    /**
     * @var int
     * @Column(name="isa_issue_id", type="integer", nullable=false)
     */
    private $isa_issue_id;

    /**
     * @var int
     * @Column(name="isa_associated_id", type="integer", nullable=false)
     */
    private $isa_associated_id;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->isa_id;
    }

    /**
     * Set Issue Id
     *
     * @param int $isa_issue_id
     * @return IssueAssociation
     */
    public function setIssueId($isa_issue_id)
    {
        $this->isa_issue_id = $isa_issue_id;

        return $this;
    }

    /**
     * Get Issue Id
     *
     * @return int
     */
    public function getIssueId()
    {
        return $this->isa_issue_id;
    }

    /**
     * Set associated Issue Id
     *
     * @param int $isa_associated_id
     * @return IssueAssociation
     */
    public function setAssociatedId($isa_associated_id)
    {
        $this->isa_associated_id = $isa_associated_id;

        return $this;
    }

    /**
     * Get associated Issue Id
     *
     * @return int
     */
    public function getAssociatedId()
    {
        return $this->isa_associated_id;
    }
}
