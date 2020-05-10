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
use Doctrine\ORM\PersistentCollection;

/**
 * @ORM\Table(name="project", uniqueConstraints={@ORM\UniqueConstraint(name="prj_title", columns={"prj_title"})}, indexes={@ORM\Index(name="prj_lead_usr_id", columns={"prj_lead_usr_id"})})
 * @ORM\Entity(repositoryClass="Eventum\Model\Repository\ProjectRepository")
 */
class Project
{
    private const DATE_FORMAT = 'Y-m-d H:i:s';

    /**
     * @var int
     * @ORM\Column(name="prj_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var DateTime
     * @ORM\Column(name="prj_created_date", type="datetime", nullable=false)
     */
    private $createdDate;

    /**
     * @var string
     * @ORM\Column(name="prj_title", type="string", length=64, nullable=false)
     */
    private $title;

    /**
     * @var string
     * @ORM\Column(name="prj_status", type="string", nullable=false)
     */
    private $status;

    /**
     * @var int
     * @ORM\Column(name="prj_lead_usr_id", type="integer", nullable=false)
     */
    private $leadUserId;

    /**
     * @var int
     * @ORM\Column(name="prj_initial_sta_id", type="integer", nullable=false)
     */
    private $initialStatusId;

    /**
     * @var string
     * @ORM\Column(name="prj_remote_invocation", type="string", length=8, nullable=false)
     */
    private $remoteInvocation;

    /**
     * @var string
     * @ORM\Column(name="prj_anonymous_post", type="string", length=8, nullable=false)
     */
    private $anonymousPost;

    /**
     * @var string
     * @ORM\Column(name="prj_anonymous_post_options", type="text", length=65535, nullable=true)
     */
    private $anonymousPostOptions;

    /**
     * @var string
     * @ORM\Column(name="prj_outgoing_sender_name", type="string", length=255, nullable=false)
     */
    private $outgoingSenderName;

    /**
     * @var string
     * @ORM\Column(name="prj_outgoing_sender_email", type="string", length=255, nullable=false)
     */
    private $outgoingSenderEmail;

    /**
     * @var string
     * @ORM\Column(name="prj_sender_flag", type="string", length=255, nullable=true)
     */
    private $senderFlag;

    /**
     * @var string
     * @ORM\Column(name="prj_sender_flag_location", type="string", length=6, nullable=true)
     */
    private $senderFlagLocation;

    /**
     * @var string
     * @ORM\Column(name="prj_mail_aliases", type="string", length=255, nullable=true)
     */
    private $mailAliases;

    /**
     * @var string
     * @ORM\Column(name="prj_customer_backend", type="string", length=64, nullable=true)
     */
    private $customerBackend;

    /**
     * @var string
     * @ORM\Column(name="prj_workflow_backend", type="string", length=64, nullable=true)
     */
    private $workflowBackend;

    /**
     * @var bool
     * @ORM\Column(name="prj_segregate_reporter", type="boolean", nullable=true)
     */
    private $segregateReporter;

    /**
     * @var ProjectCustomField[]|PersistentCollection
     * @ORM\OneToMany(targetEntity="ProjectCustomField", mappedBy="project")
     * @ORM\JoinColumn(name="id", referencedColumnName="pcf_prj_id")
     */
    public $customField;

    public function getId(): int
    {
        return $this->id;
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

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setLeadUserId(int $leadUserId): self
    {
        $this->leadUserId = $leadUserId;

        return $this;
    }

    public function getLeadUserId(): int
    {
        return $this->leadUserId;
    }

    public function setInitialStatusId(int $initialStatusId): self
    {
        $this->initialStatusId = $initialStatusId;

        return $this;
    }

    public function getInitialStatusId(): int
    {
        return $this->initialStatusId;
    }

    public function setRemoteInvocation(string $remoteInvocation): self
    {
        $this->remoteInvocation = $remoteInvocation;

        return $this;
    }

    public function getRemoteInvocation(): string
    {
        return $this->remoteInvocation;
    }

    public function setAnonymousPost(string $anonymousPost): self
    {
        $this->anonymousPost = $anonymousPost;

        return $this;
    }

    public function getAnonymousPost(): string
    {
        return $this->anonymousPost;
    }

    public function setAnonymousPostOptions(?string $anonymousPostOptions): self
    {
        $this->anonymousPostOptions = $anonymousPostOptions;

        return $this;
    }

    public function getAnonymousPostOptions(): ?string
    {
        return $this->anonymousPostOptions;
    }

    public function setOutgoingSenderName(string $outgoingSenderName): self
    {
        $this->outgoingSenderName = $outgoingSenderName;

        return $this;
    }

    public function getOutgoingSenderName(): string
    {
        return $this->outgoingSenderName;
    }

    public function setOutgoingSenderEmail(string $outgoingSenderEmail): self
    {
        $this->outgoingSenderEmail = $outgoingSenderEmail;

        return $this;
    }

    public function getOutgoingSenderEmail(): string
    {
        return $this->outgoingSenderEmail;
    }

    public function setSenderFlag(?string $senderFlag): self
    {
        $this->senderFlag = $senderFlag;

        return $this;
    }

    public function getSenderFlag(): ?string
    {
        return $this->senderFlag;
    }

    public function setSenderFlagLocation(?string $senderFlagLocation): self
    {
        $this->senderFlagLocation = $senderFlagLocation;

        return $this;
    }

    public function getSenderFlagLocation(): ?string
    {
        return $this->senderFlagLocation;
    }

    public function setMailAliases(?string $mailAliases): self
    {
        $this->mailAliases = $mailAliases;

        return $this;
    }

    public function getMailAliases(): ?string
    {
        return $this->mailAliases;
    }

    public function setCustomerBackend(?string $customerBackend): self
    {
        $this->customerBackend = $customerBackend;

        return $this;
    }

    public function getCustomerBackend(): ?string
    {
        return $this->customerBackend;
    }

    public function setWorkflowBackend(?string $workflowBackend): self
    {
        $this->workflowBackend = $workflowBackend;

        return $this;
    }

    public function getWorkflowBackend(): ?string
    {
        return $this->workflowBackend;
    }

    public function setSegregateReporter(bool $segregateReporter): self
    {
        $this->segregateReporter = $segregateReporter;

        return $this;
    }

    public function getSegregateReporter(): bool
    {
        return $this->segregateReporter;
    }

    public function toArray(): array
    {
        return [
            'prj_id' => $this->getId(),
            'prj_created_date' => $this->createdDate->format(self::DATE_FORMAT),
            'prj_title' => $this->getTitle(),
            'prj_status' => $this->getStatus(),
            'prj_lead_usr_id' => $this->getLeadUserId(),
            'prj_initial_sta_id' => $this->getInitialStatusId(),
            'prj_remote_invocation' => $this->getRemoteInvocation(),
            'prj_anonymous_post' => $this->getAnonymousPost(),
            'prj_anonymous_post_options' => $this->getAnonymousPostOptions(),
            'prj_outgoing_sender_name' => $this->getOutgoingSenderName(),
            'prj_outgoing_sender_email' => $this->getOutgoingSenderEmail(),
            'prj_sender_flag' => $this->getSenderFlag(),
            'prj_sender_flag_location' => $this->getSenderFlagLocation(),
            'prj_mail_aliases' => $this->getMailAliases(),
            'prj_customer_backend' => $this->getCustomerBackend(),
            'prj_workflow_backend' => $this->getWorkflowBackend(),
            'prj_segregate_reporter' => (string)(int)$this->getSegregateReporter(),
        ];
    }
}
