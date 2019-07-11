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

use Date_Helper;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Eventum\CustomField\Fields\ListInterface;
use Eventum\CustomField\Fields\OptionValueInterface;
use Exception;

/**
 * @ORM\Table(name="issue_custom_field", indexes={@ORM\Index(name="icf_iss_id", columns={"icf_iss_id"}), @ORM\Index(name="icf_fld_id", columns={"icf_fld_id"}), @ORM\Index(name="ft_icf_value", columns={"icf_value"})})
 * @ORM\Entity
 */
class IssueCustomField
{
    private const DATE_FORMAT = 'Y-m-d';

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

    public function setStringValue(?string $value): self
    {
        $this->integerValue = null;
        $this->dateValue = null;
        $this->stringValue = $value;

        return $this;
    }

    public function getStringValue(): ?string
    {
        return $this->stringValue;
    }

    public function setIntegerValue(?int $value): self
    {
        $this->stringValue = null;
        $this->dateValue = null;
        $this->integerValue = $value;

        return $this;
    }

    public function getIntegerValue(): ?int
    {
        return $this->integerValue;
    }

    public function setDateValue($value): self
    {
        if ($value && !$value instanceof DateTime) {
            try {
                $value = Date_Helper::getDateTime($value, 'GMT');
            } catch (Exception $e) {
                return $this;
            }
        }

        $this->stringValue = null;
        $this->integerValue = null;
        $this->dateValue = $value ?: null;

        return $this;
    }

    public function getDateValue(): ?DateTime
    {
        return $this->dateValue;
    }

    public function getDate(): ?string
    {
        if (!$this->dateValue) {
            return null;
        }

        // avoid values like '-0001-11-30 00:00:00.000000'
        // timestamp value on 64bit system is -62169989940
        if ($this->dateValue->getTimestamp() < 0) {
            return null;
        }

        return $this->dateValue->format(self::DATE_FORMAT);
    }

    /**
     * @return DateTime|int|null|string
     */
    public function getValue()
    {
        switch ($this->customField->getType()) {
            case 'date':
                return $this->getDate();
            case 'integer':
                return $this->getIntegerValue();
            default:
                return $this->getStringValue();
        }
    }

    public function getDisplayValue(): ?string
    {
        if ($this->customField->isOptionType()) {
            return $this->getOptionValue();
        }

        return $this->getValue();
    }

    public function setValue(?string $value): self
    {
        switch ($this->customField->getType()) {
            case 'date':
                return $this->setDateValue($value);
            case 'integer':
                return $this->setIntegerValue($value);
            default:
                return $this->setStringValue($value);
        }
    }

    public function setCustomField(CustomField $cf): self
    {
        $this->customField = $cf;
        $this->fieldId = $cf->getId();
        $cf->addIssue($this);

        return $this;
    }

    public function getOptionValue(): ?string
    {
        $cf = $this->customField;
        $value = $this->getValue();

        // FIXME: why?
        if (!$value) {
            return $value;
        }

        $backend = $cf->getProxy();
        $fld_id = $cf->getId();

        if ($backend && $backend->hasInterface(OptionValueInterface::class)) {
            return $backend->getOptionValue($fld_id, $value);
        }

        if ($backend && $backend->hasInterface(ListInterface::class)) {
            $values = $backend->getList($fld_id, false);

            return $values[$value] ?? null;
        }

        if (!is_numeric($value)) {
            // wrong type, log it?
            return null;
        }

        $cfo = $cf->getOptionById($value);

        return $cfo ? $cfo->getValue() : null;
    }

    /**
     * Analyzes the contents of the issue_custom_field and updates contents based on the fld_type.
     */
    public function updateValuesForNewType(): self
    {
        switch ($this->customField->getType()) {
            case 'date':
                // XXX converting previous integer or text to date makes no sense!
                $value = $this->getDate() ?? $this->getStringValue();

                return $this->setDateValue($value);

            case 'integer':
                // XXX converting from date makes no sense!
                $value = $this->getIntegerValue() ?? $this->getStringValue() ?? $this->getDate();

                return $this->setIntegerValue($value);

            default:
                $value = $this->getStringValue() ?? $this->getIntegerValue() ?? $this->getDate();

                return $this->setStringValue($value);
        }
    }
}
