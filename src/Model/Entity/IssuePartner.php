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

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="issue_partner")
 * @ORM\Entity(repositoryClass="Eventum\Model\Repository\IssuePartnerRepository")
 */
class IssuePartner
{
    /**
     * @var int
     * @ORM\Column(name="ipa_iss_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $issueId;

    /**
     * @var string
     * @ORM\Column(name="ipa_par_code", type="string", length=255, nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $partnerCode;

    /**
     * @var DateTime
     * @ORM\Column(name="ipa_created_date", type="datetime", nullable=false)
     */
    private $createdDate;

    public function __construct(int $issueId, string $partnerCode)
    {
        $this->issueId = $issueId;
        $this->partnerCode = $partnerCode;
        $this->createdDate = new DateTime();
    }

    public function setIssueId(int $issueId): self
    {
        $this->issueId = $issueId;

        return $this;
    }

    public function getIssueId(): int
    {
        return $this->issueId;
    }

    public function setPartnerCode(string $partnerCode): self
    {
        $this->partnerCode = $partnerCode;

        return $this;
    }

    public function getPartnerCode(): string
    {
        return $this->partnerCode;
    }

    public function setCreatedDate(DateTime $createdDate): self
    {
        $this->createdDate = $createdDate;

        return $this;
    }

    public function getCreatedDate(): DateTime
    {
        return $this->createdDate;
    }
}
