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
 * @ORM\Table(name="issue_custom_field", indexes={@ORM\Index(name="icf_iss_id", columns={"icf_iss_id"}), @ORM\Index(name="icf_fld_id", columns={"icf_fld_id"}), @ORM\Index(name="ft_icf_value", columns={"icf_value"})})
 * @ORM\Entity
 */
class IssueCustomField
{
    /**
     * @var int
     * @ORM\Column(name="icf_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var int
     * @ORM\Column(name="icf_iss_id", type="integer", nullable=false)
     */
    private $issueId;

    /**
     * @var int
     * @ORM\Column(name="icf_fld_id", type="integer", nullable=false)
     */
    private $fieldId;

    /**
     * @var CustomField
     * @ORM\ManyToOne(targetEntity="CustomField", inversedBy="issues")
     * @ORM\JoinColumn(name="icf_fld_id", referencedColumnName="fld_id")
     */
    public $customField;

    /**
     * @var string
     * @ORM\Column(name="icf_value", type="text", length=65535, nullable=true)
     */
    private $stringValue;

    /**
     * @var int
     * @ORM\Column(name="icf_value_integer", type="integer", nullable=true)
     */
    private $integerValue;

    /**
     * @var DateTime
     * @ORM\Column(name="icf_value_date", type="date", nullable=true)
     */
    private $dateValue;

    public function getId(): int
    {
        return $this->id;
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

    public function setFieldId(int $fieldId): self
    {
        $this->fieldId = $fieldId;

        return $this;
    }

    public function getFieldId(): int
    {
        return $this->fieldId;
    }

    public function setStringValue(?string $stringValue): self
    {
        $this->stringValue = $stringValue;

        return $this;
    }

    public function getStringValue(): ?string
    {
        return $this->stringValue;
    }

    public function setIntegerValue(?int $integerValue): self
    {
        $this->integerValue = $integerValue;

        return $this;
    }

    public function getIntegerValue(): ?int
    {
        return $this->integerValue;
    }

    public function setDateValue(?DateTime $dateValue): self
    {
        $this->dateValue = $dateValue;

        return $this;
    }

    public function getDateValue(): ?DateTime
    {
        return $this->dateValue;
    }
}
