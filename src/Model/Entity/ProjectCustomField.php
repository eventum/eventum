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
 * @ORM\Table(name="project_custom_field", indexes={@ORM\Index(name="pcf_prj_id", columns={"pcf_prj_id"}), @ORM\Index(name="pcf_fld_id", columns={"pcf_fld_id"})})
 * @ORM\Entity
 */
class ProjectCustomField
{
    /**
     * @var int
     * @ORM\Column(name="pcf_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var int
     * @ORM\Column(name="pcf_prj_id", type="integer", nullable=false)
     */
    private $projectId;

    /**
     * @var Project
     * @ORM\ManyToOne(targetEntity="Project", inversedBy="customField")
     * @ORM\JoinColumn(name="pcf_prj_id", referencedColumnName="prj_id", nullable=false)
     */
    private $project;

    /**
     * TODO: drop, if could know where to move the pcf_fld_id field declaration
     * @var int
     * @ORM\Column(name="pcf_fld_id", type="integer", nullable=false)
     */
    private $fieldId;

    /**
     * @var CustomField
     * @ORM\ManyToOne(targetEntity="CustomField", inversedBy="projects")
     * @ORM\JoinColumn(name="pcf_fld_id", referencedColumnName="fld_id", nullable=false)
     */
    private $customField;

    public function getId(): int
    {
        return $this->id;
    }

    public function setProject(Project $project): self
    {
        $this->project = $project;
        $this->projectId = $project->getId();

        return $this;
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function getProjectId(): int
    {
        return $this->project->getId();
    }

    public function setCustomField(CustomField $cf): self
    {
        $this->customField = $cf;
        $this->fieldId = $cf->getId();

        return $this;
    }
}
