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
 * @ORM\Table(name="custom_field_option", indexes={@ORM\Index(name="icf_fld_id", columns={"cfo_fld_id"})})
 * @ORM\Entity
 */
class CustomFieldOption
{
    /**
     * @var int
     * @ORM\Column(name="cfo_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var int
     * @ORM\Column(name="cfo_fld_id", type="integer", nullable=false)
     */
    private $fieldId;

    /**
     * @var CustomField
     * @ORM\ManyToOne(targetEntity="CustomField", inversedBy="options")
     * @ORM\JoinColumn(name="cfo_fld_id", referencedColumnName="fld_id")
     */
    private $customField;

    /**
     * @var int
     * @ORM\Column(name="cfo_rank", type="integer", nullable=false)
     */
    private $rank;

    /**
     * @var string
     * @ORM\Column(name="cfo_value", type="string", length=128, nullable=false)
     */
    private $value;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setCustomField(CustomField $cf): self
    {
        $this->customField = $cf;
        $this->fieldId = $cf->getId();

        return $this;
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

    public function setRank(int $rank): self
    {
        $this->rank = $rank;

        return $this;
    }

    public function getRank(): int
    {
        return $this->rank;
    }

    public function setValue(string $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
