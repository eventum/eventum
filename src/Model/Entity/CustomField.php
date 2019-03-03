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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Eventum\CustomField\Factory;
use Eventum\CustomField\Fields\ListInterface;
use Eventum\CustomField\Proxy;
use Eventum\Monolog\Logger;
use InvalidArgumentException;
use RuntimeException;

/**
 * @ORM\Table(name="custom_field")*
 * @ORM\Entity(repositoryClass="Eventum\Model\Repository\CustomFieldRepository")
 */
class CustomField
{
    public const LIST_DISPLAY = 'list_display';

    public const FORM_TYPES = [
        // fld_<type> list
        // note "edit" form always enabled
        'report_form' => 'showReportForm',
        'anonymous_form' => 'showAnonymousForm',
        'close_form' => 'showCloseForm',
        self::LIST_DISPLAY => 'showListDisplay',
        'edit_form' => null,
    ];

    private const OPTION_TYPES = [
        'checkbox',
        'combo',
        'multiple',
    ];

    private const TEXT_TYPES = [
        'text',
        'textarea',
    ];

    private const OTHER_TYPES = [
        'text',
        'textarea',
        'date',
        'integer',
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
     * @ORM\Column(name="fld_min_role_edit", type="integer", nullable=false)
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

    /** @var Proxy|null */
    private $proxy;

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
    private $options;

    /**
     * @var IssueCustomField[]|PersistentCollection
     * @ORM\OneToMany(targetEntity="IssueCustomField", mappedBy="customField")
     * @ORM\JoinColumn(name="id", referencedColumnName="icf_iss_id")
     */
    public $issues;

    /**
     * @var ProjectCustomField[]|PersistentCollection
     * @ORM\OneToMany(targetEntity="ProjectCustomField", mappedBy="customField")
     * @ORM\JoinColumn(name="id", referencedColumnName="pcf_prj_id")
     */
    private $projects;

    public function __construct()
    {
        $this->projects = new ArrayCollection();
    }

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
        $this->backend = $backend ?: null;

        return $this;
    }

    public function getBackend(): ?Proxy
    {
        if ($this->proxy === null && $this->backend) {
            try {
                $this->proxy = Factory::create($this->backend);
            } catch (InvalidArgumentException $e) {
                Logger::app()->error("Could not load backend {$this->backend}", ['exception' => $e]);
                $this->proxy = false;
            }
        }

        return $this->proxy ?: null;
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

    public function isOptionType(): bool
    {
        return in_array($this->type, self::OPTION_TYPES, true);
    }

    public function isTextType(): bool
    {
        return in_array($this->type, self::TEXT_TYPES, true);
    }

    public function isOtherType(): bool
    {
        return in_array($this->type, self::OTHER_TYPES, true);
    }

    public function addOption(CustomFieldOption $cfo): self
    {
        $this->options->add($cfo);

        return $this;
    }

    public function addIssue(IssueCustomField $icf): self
    {
        $this->issues->add($icf);

        return $this;
    }

    public function getOptions(): Collection
    {
        [$columnName, $direction] = explode(' ', $this->orderBy);

        $columnName = $this->options->getTypeClass()->getFieldName($columnName);
        $criteria = Criteria::create()->orderBy([$columnName => $direction]);

        return $this->options->matching($criteria);
    }

    /**
     * @see Custom_Field::getOptions
     */
    public function getOptionValues(?string $formType = null, ?int $issueId = null): array
    {
        $backend = $this->getBackend();

        if ($backend && $backend->hasInterface(ListInterface::class)) {
            return $backend->getList($this->getId(), $issueId, $formType);
        }

        $result = [];
        foreach ($this->getOptions() as $option) {
            $result[$option->getId()] = $option->getValue();
        }

        return $result;
    }

    public function getIssueOptionValues(int $issue_id): Collection
    {
        return $this->getIssueCustomFields($issue_id)->map(function (IssueCustomField $icf) {
            return $icf->getValue();
        });
    }

    /**
     * @return Collection|IssueCustomField[]
     */
    public function getIssues(): Collection
    {
        return $this->issues;
    }

    public function updateOptionValue(int $cfo_id, string $value, int $rank): CustomFieldOption
    {
        $cfo = $this->getOptionById($cfo_id);
        if (!$cfo) {
            $cfo = new CustomFieldOption();
            $cfo->setCustomField($this);
        }

        $cfo->setValue($value);
        $cfo->setRank($rank);

        return $cfo;
    }

    public function updateIssueCustomField(int $issue_id, string $value): IssueCustomField
    {
        return $this->getIssueCustomFields($issue_id)
            ->filter(function (IssueCustomField $icf) use ($value) {
                return $icf->getValue() === $value;
            })
            ->first() ?: $this->addIssueCustomField($issue_id, $value);
    }

    public function addIssueCustomField(int $issue_id, string $value): IssueCustomField
    {
        $icf = new IssueCustomField();
        $icf->setIssueId($issue_id);
        $icf->setCustomField($this);
        $icf->setValue($value);

        return $icf;
    }

    public function addOptionValue(string $value, int $rank): CustomFieldOption
    {
        $cfo = new CustomFieldOption();
        $cfo->setCustomField($this);
        $cfo->setValue($value);
        $cfo->setRank($rank);

        return $cfo;
    }

    public function getOptionById(int $cfo_id): ?CustomFieldOption
    {
        $expr = new Comparison('id', '=', $cfo_id);
        $criteria = Criteria::create()->where($expr);

        return $this->getOne($this->options, $criteria);
    }

    /**
     * @return ProjectCustomField[]|Collection
     */
    public function getProjectCustomFields(): Collection
    {
        return $this->projects;
    }

    public function addProjectCustomField(ProjectCustomField $pcf): self
    {
        $pcf->setCustomField($this);
        $this->projects->add($pcf);

        return $this;
    }

    public function getProjectCustomFieldById(int $prj_id): ?ProjectCustomField
    {
        $expr = new Comparison('projectId', '=', $prj_id);
        $criteria = Criteria::create()->where($expr);

        return $this->getOne($this->projects, $criteria);
    }

    public function getIssueCustomField(int $issue_id): ?IssueCustomField
    {
        $expr = new Comparison('issueId', '=', $issue_id);
        $criteria = Criteria::create()->where($expr);

        return $this->getOne($this->issues, $criteria) ?: null;
    }

    /**
     * @return Collection|IssueCustomField[]
     */
    public function getIssueCustomFields(int $issue_id): Collection
    {
        $expr = new Comparison('issueId', '=', $issue_id);
        $criteria = Criteria::create()->where($expr);

        return $this->issues->matching($criteria);
    }

    private function getOne(PersistentCollection $collection, Criteria $criteria)
    {
        $matches = $collection->matching($criteria);
        if ($matches->isEmpty()) {
            return null;
        }

        if ($matches->count() !== 1) {
            $count = $matches->count();
            throw new RuntimeException("Expected one element, got $count");
        }

        return $matches->first();
    }

    public function getDisplayValue(int $issue_id): string
    {
        $values = $this->getIssueCustomFields($issue_id)
            ->map(function (IssueCustomField $icf) {
                return $icf->getDisplayValue();
            });

        return implode(', ', $values->toArray());
    }

    public function toArray(): array
    {
        return [
            'fld_id' => $this->getId(),
            'fld_title' => $this->getTitle(),
            'fld_type' => $this->getType(),
            'fld_rank' => $this->getRank(),
            'fld_order_by' => $this->getOrderBy(),
            'fld_report_form_required' => (string)(int)$this->isReportFormRequired(),
            'fld_anonymous_form_required' => (string)(int)$this->isAnonymousFormRequired(),
            'fld_close_form_required' => (string)(int)$this->isCloseFormRequired(),
            'fld_edit_form_required' => (string)(int)$this->isEditFormRequired(),
            'fld_min_role' => $this->getMinRole(),
            'fld_description' => $this->getDescription(),
        ];
    }
}
