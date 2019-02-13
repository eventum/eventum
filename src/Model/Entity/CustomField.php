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

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use RuntimeException;

/**
 * @ORM\Table(name="custom_field")*
 * @ORM\Entity(repositoryClass="Eventum\Model\Repository\CustomFieldRepository")
 */
class CustomField
{
    public const FORM_TYPES = [
        // fld_<type> list
        // note "edit" form always enabled
        'report_form' => 'showReportForm',
        'anonymous_form' => 'showAnonymousForm',
        'close_form' => 'showCloseForm',
        'edit_form' => null,
    ];

    /**
     * @var int
     * @ORM\Column(name="fld_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="fld_title", type="string", length=32, nullable=false)
     */
    private $title;

    /**
     * @var string
     * @ORM\Column(name="fld_description", type="string", length=64, nullable=true)
     */
    private $description;

    /**
     * @var string
     * @ORM\Column(name="fld_type", type="string", length=8, nullable=false)
     */
    private $type;

    /**
     * @var bool
     * @ORM\Column(name="fld_report_form", type="boolean", nullable=false)
     */
    private $showReportForm;

    /**
     * @var bool
     * @ORM\Column(name="fld_report_form_required", type="boolean", nullable=false)
     */
    private $isReportFormRequired;

    /**
     * @var bool
     * @ORM\Column(name="fld_anonymous_form", type="boolean", nullable=false)
     */
    private $showAnonymousForm;

    /**
     * @var bool
     * @ORM\Column(name="fld_anonymous_form_required", type="boolean", nullable=false)
     */
    private $isAnonymousFormRequired;

    /**
     * @var bool
     * @ORM\Column(name="fld_close_form", type="boolean", nullable=false)
     */
    private $showCloseForm;

    /**
     * @var bool
     * @ORM\Column(name="fld_close_form_required", type="boolean", nullable=false)
     */
    private $isCloseFormRequired;

    /**
     * @var bool
     * @ORM\Column(name="fld_edit_form_required", type="boolean", nullable=false)
     */
    private $isEditFormRequired;

    /**
     * @var bool
     * @ORM\Column(name="fld_list_display", type="boolean", nullable=false)
     */
    private $showListDisplay;

    /**
     * @var int
     * @ORM\Column(name="fld_min_role", type="integer", nullable=false)
     */
    private $minRole;

    /**
     * @var bool
     * @ORM\Column(name="fld_min_role_edit", type="boolean", nullable=false)
     */
    private $minRoleEdit;

    /**
     * @var int
     * @ORM\Column(name="fld_rank", type="smallint", nullable=false)
     */
    private $rank;

    /**
     * @var string
     * @ORM\Column(name="fld_backend", type="string", length=255, nullable=true)
     */
    private $backend;

    /**
     * @var string
     * @ORM\Column(name="fld_order_by", type="string", length=20, nullable=false)
     */
    private $orderBy;

    /**
     * @var CustomFieldOption[]|PersistentCollection
     * @ORM\OneToMany(targetEntity="CustomFieldOption", mappedBy="customField")
     * @ORM\JoinColumn(name="id", referencedColumnName="cfo_fld_id")
     * @ORM\OrderBy({"id" = "ASC"})
     */
    public $options;

    /**
     * @var IssueCustomField[]|PersistentCollection
     * @ORM\OneToMany(targetEntity="IssueCustomField", mappedBy="customField")
     * @ORM\JoinColumn(name="id", referencedColumnName="icf_iss_id")
     */
    public $issues;

    /**
     * @var ProjectCustomField[]
     * @ORM\OneToMany(targetEntity="ProjectCustomField", mappedBy="customField")
     * @ORM\JoinColumn(name="id", referencedColumnName="icf_iss_id")
     */
    public $projects;

    public function getId(): int
    {
        return $this->id;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setShowReportForm(bool $enabled): self
    {
        $this->showReportForm = $enabled;

        return $this;
    }

    public function showReportForm(): bool
    {
        return $this->showReportForm;
    }

    public function setIsReportFormRequired(bool $required): self
    {
        $this->isReportFormRequired = $required;

        return $this;
    }

    public function isReportFormRequired(): bool
    {
        return $this->isReportFormRequired;
    }

    public function setShowAnonymousForm(bool $enabled): self
    {
        $this->showAnonymousForm = $enabled;

        return $this;
    }

    public function showAnonymousForm(): bool
    {
        return $this->showAnonymousForm;
    }

    public function setIsAnonymousFormRequired(bool $required): self
    {
        $this->isAnonymousFormRequired = $required;

        return $this;
    }

    public function isAnonymousFormRequired(): bool
    {
        return $this->isAnonymousFormRequired;
    }

    public function setShowCloseForm(bool $enabled): self
    {
        $this->showCloseForm = $enabled;

        return $this;
    }

    public function showCloseForm(): bool
    {
        return $this->showCloseForm;
    }

    public function setIsCloseFormRequired(bool $required): self
    {
        $this->isCloseFormRequired = $required;

        return $this;
    }

    public function isCloseFormRequired(): bool
    {
        return $this->isCloseFormRequired;
    }

    public function setIsEditFormRequired(bool $required): self
    {
        $this->isEditFormRequired = $required;

        return $this;
    }

    public function isEditFormRequired(): bool
    {
        return $this->isEditFormRequired;
    }

    public function setShowListDisplay(bool $showListDisplay): self
    {
        $this->showListDisplay = $showListDisplay;

        return $this;
    }

    public function showListDisplay(): bool
    {
        return $this->showListDisplay;
    }

    public function setMinRole(int $minRole): self
    {
        $this->minRole = $minRole;

        return $this;
    }

    public function getMinRole(): int
    {
        return $this->minRole;
    }

    public function setMinRoleEdit(int $minRoleEdit): self
    {
        $this->minRoleEdit = $minRoleEdit;

        return $this;
    }

    public function getMinRoleEdit(): int
    {
        return $this->minRoleEdit;
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

    public function setBackend(?string $backend): self
    {
        $this->backend = $backend;

        return $this;
    }

    public function getBackend(): ?string
    {
        return $this->backend;
    }

    public function setOrderBy(string $orderBy): self
    {
        $this->orderBy = $orderBy;

        return $this;
    }

    public function getOrderBy(): string
    {
        return $this->orderBy;
    }

    public function getSortedOptions(string $orderBy): Collection
    {
        [$columnName, $direction] = explode(' ', $orderBy);

        $columnName = $this->options->getTypeClass()->getFieldName($columnName);
        $criteria = Criteria::create()->orderBy([$columnName => $direction]);

        return $this->options->matching($criteria);
    }

    public function getOptions(): array
    {
        $options = $this->getSortedOptions($this->orderBy);

        $result = [];
        /** @var CustomFieldOption $option */
        foreach ($options as $option) {
            $result[$option->getId()] = $option->getValue();
        }

        return $result;
    }

    public function getOptionById(int $cfo_id): ?CustomFieldOption
    {
        $expr = new Comparison('id', '=', $cfo_id);
        $criteria = Criteria::create()->where($expr);

        $collection = $this->options->matching($criteria);
        if (!in_array($collection->count(), [0, 1], true)) {
            $count = $collection->count();
            throw new RuntimeException("Expected 0 or 1, got $count");
        }

        return $collection->first();
    }

    /**
     * The doctrine join probably wrong, we get excess relations with wrong issues
     */
    public function getMatchingIssues(int $issue_id): Collection
    {
        $expr = new Comparison('issueId', '=', $issue_id);
        $criteria = Criteria::create()->where($expr);

        return $this->issues->matching($criteria);
    }
}
