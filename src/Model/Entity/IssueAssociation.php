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

use Doctrine\ORM\Mapping as ORM;

/**
 * IssueAssociation
 *
 * @ORM\Table(name="issue_association", indexes={@ORM\Index(name="isa_issue_id", columns={"isa_issue_id", "isa_associated_id"})})
 * @ORM\Entity(repositoryClass="Eventum\Model\Repository\IssueAssociationRepository")
 */
class IssueAssociation
{
    /**
     * @var int
     * @ORM\Column(name="isa_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $isa_id;

    /**
     * @var int
     * @ORM\Column(name="isa_issue_id", type="integer", nullable=false)
     */
    private $isa_issue_id;

    /**
     * @var int
     * @ORM\Column(name="isa_associated_id", type="integer", nullable=false)
     */
    private $isa_associated_id;

    public function getId(): int
    {
        return $this->isa_id;
    }

    public function setIssueId(int $issueId): self
    {
        $this->isa_issue_id = $issueId;

        return $this;
    }

    public function getIssueId(): int
    {
        return $this->isa_issue_id;
    }

    public function setAssociatedIssueId(int $associatedIssueId): self
    {
        $this->isa_associated_id = $associatedIssueId;

        return $this;
    }

    public function getAssociatedIssueId(): int
    {
        return $this->isa_associated_id;
    }
}
