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
 * Project
 *
 * @Table(name="project", uniqueConstraints={@UniqueConstraint(name="prj_title", columns={"prj_title"})}, indexes={@Index(name="prj_lead_usr_id", columns={"prj_lead_usr_id"})})
 * @Entity(repositoryClass="Eventum\Model\Repository\ProjectRepository")
 */
class Project
{
    /**
     * @var int
     * @Column(name="prj_id", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \DateTime
     * @Column(name="prj_created_date", type="datetime", nullable=false)
     */
    private $createdDate;

    /**
     * @var string
     * @Column(name="prj_title", type="string", length=64, nullable=false)
     */
    private $title;

    /**
     * @var string
     * @Column(name="prj_status", type="string", nullable=false)
     */
    private $status;

    /**
     * @var int
     * @Column(name="prj_lead_usr_id", type="integer", nullable=false)
     */
    private $leadUserId;

    /**
     * @var int
     * @Column(name="prj_initial_sta_id", type="integer", nullable=false)
     */
    private $initialStatusId;

    /**
     * @var string
     * @Column(name="prj_remote_invocation", type="string", length=8, nullable=false)
     */
    private $remoteInvocation;

    /**
     * @var string
     * @Column(name="prj_anonymous_post", type="string", length=8, nullable=false)
     */
    private $anonymousPost;

    /**
     * @var string
     * @Column(name="prj_anonymous_post_options", type="text", length=65535, nullable=true)
     */
    private $anonymousPostOptions;

    /**
     * @var string
     * @Column(name="prj_outgoing_sender_name", type="string", length=255, nullable=false)
     */
    private $outgoingSenderName;

    /**
     * @var string
     * @Column(name="prj_outgoing_sender_email", type="string", length=255, nullable=false)
     */
    private $outgoingSenderEmail;

    /**
     * @var string
     * @Column(name="prj_sender_flag", type="string", length=255, nullable=true)
     */
    private $senderFlag;

    /**
     * @var string
     * @Column(name="prj_sender_flag_location", type="string", length=6, nullable=true)
     */
    private $senderFlagLocation;

    /**
     * @var string
     * @Column(name="prj_mail_aliases", type="string", length=255, nullable=true)
     */
    private $mailAliases;

    /**
     * @var string
     * @Column(name="prj_customer_backend", type="string", length=64, nullable=true)
     */
    private $customerBackend;

    /**
     * @var string
     * @Column(name="prj_workflow_backend", type="string", length=64, nullable=true)
     */
    private $workflowBackend;

    /**
     * @var bool
     * @Column(name="prj_segregate_reporter", type="boolean", nullable=true)
     */
    private $segregateReporter;

    /**
     * Get prjId
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set prjCreatedDate
     *
     * @param \DateTime $createdDate
     * @return Project
     */
    public function setCreatedDate($createdDate)
    {
        $this->createdDate = $createdDate;

        return $this;
    }

    /**
     * Get prjCreatedDate
     *
     * @return \DateTime
     */
    public function getCreatedDate()
    {
        return $this->createdDate;
    }

    /**
     * Set prjTitle
     *
     * @param string $title
     * @return Project
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get prjTitle
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set prjStatus
     *
     * @param string $status
     * @return Project
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get prjStatus
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set prjLeadUsrId
     *
     * @param int $leadUserId
     * @return Project
     */
    public function setLeadUserId($leadUserId)
    {
        $this->leadUserId = $leadUserId;

        return $this;
    }

    /**
     * Get prjLeadUsrId
     *
     * @return int
     */
    public function getLeadUserId()
    {
        return $this->leadUserId;
    }

    /**
     * Set prjInitialStaId
     *
     * @param int $initialStatusId
     * @return Project
     */
    public function setInitialStatusId($initialStatusId)
    {
        $this->initialStatusId = $initialStatusId;

        return $this;
    }

    /**
     * Get prjInitialStaId
     *
     * @return int
     */
    public function getInitialStatusId()
    {
        return $this->initialStatusId;
    }

    /**
     * Set prjRemoteInvocation
     *
     * @param string $remoteInvocation
     * @return Project
     */
    public function setRemoteInvocation($remoteInvocation)
    {
        $this->remoteInvocation = $remoteInvocation;

        return $this;
    }

    /**
     * Get prjRemoteInvocation
     *
     * @return string
     */
    public function getRemoteInvocation()
    {
        return $this->remoteInvocation;
    }

    /**
     * Set prjAnonymousPost
     *
     * @param string $anonymousPost
     * @return Project
     */
    public function setAnonymousPost($anonymousPost)
    {
        $this->anonymousPost = $anonymousPost;

        return $this;
    }

    /**
     * Get prjAnonymousPost
     *
     * @return string
     */
    public function getAnonymousPost()
    {
        return $this->anonymousPost;
    }

    /**
     * Set prjAnonymousPostOptions
     *
     * @param string $anonymousPostOptions
     * @return Project
     */
    public function setAnonymousPostOptions($anonymousPostOptions)
    {
        $this->anonymousPostOptions = $anonymousPostOptions;

        return $this;
    }

    /**
     * Get prjAnonymousPostOptions
     *
     * @return string
     */
    public function getAnonymousPostOptions()
    {
        return $this->anonymousPostOptions;
    }

    /**
     * Set prjOutgoingSenderName
     *
     * @param string $outgoingSenderName
     * @return Project
     */
    public function setOutgoingSenderName($outgoingSenderName)
    {
        $this->outgoingSenderName = $outgoingSenderName;

        return $this;
    }

    /**
     * Get prjOutgoingSenderName
     *
     * @return string
     */
    public function getOutgoingSenderName()
    {
        return $this->outgoingSenderName;
    }

    /**
     * Set prjOutgoingSenderEmail
     *
     * @param string $outgoingSenderEmail
     * @return Project
     */
    public function setOutgoingSenderEmail($outgoingSenderEmail)
    {
        $this->outgoingSenderEmail = $outgoingSenderEmail;

        return $this;
    }

    /**
     * Get prjOutgoingSenderEmail
     *
     * @return string
     */
    public function getOutgoingSenderEmail()
    {
        return $this->outgoingSenderEmail;
    }

    /**
     * Set prjSenderFlag
     *
     * @param string $senderFlag
     * @return Project
     */
    public function setSenderFlag($senderFlag)
    {
        $this->senderFlag = $senderFlag;

        return $this;
    }

    /**
     * Get prjSenderFlag
     *
     * @return string
     */
    public function getSenderFlag()
    {
        return $this->senderFlag;
    }

    /**
     * Set prjSenderFlagLocation
     *
     * @param string $senderFlagLocation
     * @return Project
     */
    public function setSenderFlagLocation($senderFlagLocation)
    {
        $this->senderFlagLocation = $senderFlagLocation;

        return $this;
    }

    /**
     * Get prjSenderFlagLocation
     *
     * @return string
     */
    public function getSenderFlagLocation()
    {
        return $this->senderFlagLocation;
    }

    /**
     * Set prjMailAliases
     *
     * @param string $mailAliases
     * @return Project
     */
    public function setMailAliases($mailAliases)
    {
        $this->mailAliases = $mailAliases;

        return $this;
    }

    /**
     * Get prjMailAliases
     *
     * @return string
     */
    public function getMailAliases()
    {
        return $this->mailAliases;
    }

    /**
     * Set prjCustomerBackend
     *
     * @param string $customerBackend
     * @return Project
     */
    public function setCustomerBackend($customerBackend)
    {
        $this->customerBackend = $customerBackend;

        return $this;
    }

    /**
     * Get prjCustomerBackend
     *
     * @return string
     */
    public function getCustomerBackend()
    {
        return $this->customerBackend;
    }

    /**
     * Set prjWorkflowBackend
     *
     * @param string $workflowBackend
     * @return Project
     */
    public function setWorkflowBackend($workflowBackend)
    {
        $this->workflowBackend = $workflowBackend;

        return $this;
    }

    /**
     * Get prjWorkflowBackend
     *
     * @return string
     */
    public function getWorkflowBackend()
    {
        return $this->workflowBackend;
    }

    /**
     * Set prjSegregateReporter
     *
     * @param bool $segregateReporter
     * @return Project
     */
    public function setSegregateReporter($segregateReporter)
    {
        $this->segregateReporter = $segregateReporter;

        return $this;
    }

    /**
     * Get prjSegregateReporter
     *
     * @return bool
     */
    public function getSegregateReporter()
    {
        return $this->segregateReporter;
    }
}
