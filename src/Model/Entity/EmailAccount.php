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
 * @ORM\Table(name="email_account", uniqueConstraints={@ORM\UniqueConstraint(name="ema_username", columns={"ema_username", "ema_hostname", "ema_folder"})}, indexes={@ORM\Index(name="ema_prj_id", columns={"ema_prj_id"})})
 * @ORM\Entity
 */
class EmailAccount
{
    /**
     * @var int
     * @ORM\Column(name="ema_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var int
     * @ORM\Column(name="ema_prj_id", type="integer", nullable=false)
     */
    private $projectId;

    /**
     * Account type: imap, imap/ssl/novalidate-cert, pop3, ...
     *
     * @var string
     * @ORM\Column(name="ema_type", type="string", length=32, nullable=false)
     */
    private $type;

    /**
     * @var string
     * @ORM\Column(name="ema_folder", type="string", length=255, nullable=true)
     */
    private $folder;

    /**
     * @var string
     * @ORM\Column(name="ema_hostname", type="string", length=255, nullable=false)
     */
    private $hostname;

    /**
     * TODO: convert to integer
     * @var string
     * @ORM\Column(name="ema_port", type="string", length=5, nullable=false)
     */
    private $port;

    /**
     * @var string
     * @ORM\Column(name="ema_username", type="string", length=64, nullable=false)
     */
    private $username;

    /**
     * @var string
     * @ORM\Column(name="ema_password", type="string", length=255, nullable=false)
     */
    private $password;

    /**
     * TODO: convert to bool
     * @var int
     * @ORM\Column(name="ema_get_only_new", type="integer", nullable=false)
     */
    private $onlyNew;

    /**
     * TODO: convert to bool
     * @var int
     * @ORM\Column(name="ema_leave_copy", type="integer", nullable=false)
     */
    private $leaveCopy;

    /**
     * TODO: convert to bool
     * @var string
     * @ORM\Column(name="ema_issue_auto_creation", type="string", length=8, nullable=false)
     */
    private $issueAutoCreationEnabled;

    /**
     * TODO: use array
     * @var string
     * @ORM\Column(name="ema_issue_auto_creation_options", type="text", length=65535, nullable=true)
     */
    private $issueAutoCreationOptions;

    /**
     * @var bool
     * @ORM\Column(name="ema_use_routing", type="boolean", nullable=true)
     */
    private $useRouting;

    public function getId(): int
    {
        return $this->id;
    }

    public function setProjectId(int $projectId): self
    {
        $this->projectId = $projectId;

        return $this;
    }

    public function getProjectId(): int
    {
        return $this->projectId;
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

    public function setFolder(string $folder): self
    {
        $this->folder = $folder;

        return $this;
    }

    public function getFolder(): string
    {
        return $this->folder;
    }

    public function setHostname(string $hostname): self
    {
        $this->hostname = $hostname;

        return $this;
    }

    public function getHostname(): string
    {
        return $this->hostname;
    }

    public function setPort(string $port): self
    {
        $this->port = $port;

        return $this;
    }

    public function getPort(): string
    {
        return $this->port;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setOnlyNew(int $onlyNew): self
    {
        $this->onlyNew = $onlyNew;

        return $this;
    }

    public function getOnlyNew(): int
    {
        return $this->onlyNew;
    }

    public function setLeaveCopy(int $leaveCopy): self
    {
        $this->leaveCopy = $leaveCopy;

        return $this;
    }

    public function getLeaveCopy(): int
    {
        return $this->leaveCopy;
    }

    public function setIssueAutoCreationEnabled(string $issueAutoCreationEnabled): self
    {
        $this->issueAutoCreationEnabled = $issueAutoCreationEnabled;

        return $this;
    }

    public function hasIssueAutoCreationEnabled(): string
    {
        return $this->issueAutoCreationEnabled;
    }

    public function setIssueAutoCreationOptions(string $issueAutoCreationOptions): self
    {
        $this->issueAutoCreationOptions = $issueAutoCreationOptions;

        return $this;
    }

    public function getIssueAutoCreationOptions(): string
    {
        return $this->issueAutoCreationOptions;
    }

    public function setUseRouting(bool $useRouting): self
    {
        $this->useRouting = $useRouting;

        return $this;
    }

    public function useRouting(): bool
    {
        return $this->useRouting;
    }
}
