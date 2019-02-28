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
 * @ORM\Table(name="user_preference")
 * @ORM\Entity
 */
class UserPreference
{
    /**
     * @var int
     * @ORM\Column(name="upr_usr_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="upr_timezone", type="string", length=100, nullable=false)
     */
    private $timezone;

    /**
     * @var int
     * @ORM\Column(name="upr_week_firstday", type="integer", nullable=false)
     */
    private $weekFirstday;

    /**
     * @var int
     * @ORM\Column(name="upr_list_refresh_rate", type="integer", nullable=true)
     */
    private $listRefreshRate;

    /**
     * @var int
     * @ORM\Column(name="upr_email_refresh_rate", type="integer", nullable=true)
     */
    private $emailRefreshRate;

    /**
     * @var string
     * @ORM\Column(name="upr_email_signature", type="text", nullable=true)
     */
    private $emailSignature;

    /**
     * @var bool
     * @ORM\Column(name="upr_auto_append_email_sig", type="boolean", nullable=true)
     */
    private $autoAppendEmailSignature;

    /**
     * @var bool
     * @ORM\Column(name="upr_auto_append_note_sig", type="boolean", nullable=true)
     */
    private $autoAppendNoteSignature;

    /**
     * @var bool
     * @ORM\Column(name="upr_auto_close_popup_window", type="boolean", nullable=true)
     */
    private $autoClosePopupWindow;

    /**
     * @var bool
     * @ORM\Column(name="upr_relative_date", type="boolean", nullable=true)
     */
    private $relativeDate;

    /**
     * @var bool
     * @ORM\Column(name="upr_collapsed_emails", type="boolean", nullable=true)
     */
    private $collapsedEmails;

    /**
     * @var bool
     * @ORM\Column(name="upr_markdown", type="boolean", nullable=true)
     */
    private $enableMarkdown;

    /**
     * @var bool
     * @ORM\Column(name="upr_issue_navigation", type="boolean", nullable=false)
     */
    private $issueNavigation;

    public function getId(): int
    {
        return $this->id;
    }

    public function setTimezone(string $timezone): self
    {
        $this->timezone = $timezone;

        return $this;
    }

    public function getTimezone(): string
    {
        return $this->timezone;
    }

    public function setWeekFirstday(int $weekFirstday): self
    {
        $this->weekFirstday = $weekFirstday;

        return $this;
    }

    public function getWeekFirstday(): int
    {
        return $this->weekFirstday;
    }

    public function setListRefreshRate(int $listRefreshRate): self
    {
        $this->listRefreshRate = $listRefreshRate;

        return $this;
    }

    public function getListRefreshRate(): int
    {
        return $this->listRefreshRate;
    }

    public function setEmailRefreshRate(int $emailRefreshRate): self
    {
        $this->emailRefreshRate = $emailRefreshRate;

        return $this;
    }

    public function getEmailRefreshRate(): int
    {
        return $this->emailRefreshRate;
    }

    public function setEmailSignature(string $signature): self
    {
        $this->emailSignature = $signature;

        return $this;
    }

    public function getEmailSignature(): string
    {
        return $this->emailSignature;
    }

    public function setAutoAppendEmailSignature(bool $enable): self
    {
        $this->autoAppendEmailSignature = $enable;

        return $this;
    }

    public function autoAppendEmailSignature(): bool
    {
        return $this->autoAppendEmailSignature;
    }

    public function setAutoAppendNoteSignature(bool $enable): self
    {
        $this->autoAppendNoteSignature = $enable;

        return $this;
    }

    public function autoAppendNoteSignature(): bool
    {
        return $this->autoAppendNoteSignature;
    }

    public function setAutoClosePopupWindow(bool $enable): self
    {
        $this->autoClosePopupWindow = $enable;

        return $this;
    }

    public function autoClosePopupWindow(): bool
    {
        return $this->autoClosePopupWindow;
    }

    public function setRelativeDate(bool $enable): self
    {
        $this->relativeDate = $enable;

        return $this;
    }

    public function useRelativeDate(): bool
    {
        return $this->relativeDate;
    }

    public function setCollapsedEmails(bool $enable): self
    {
        $this->collapsedEmails = $enable;

        return $this;
    }

    public function collapseEmails(): bool
    {
        return $this->collapsedEmails;
    }

    public function setEnableMarkdown(bool $enable): self
    {
        $this->enableMarkdown = $enable;

        return $this;
    }

    public function isMarkdownEnabled(): bool
    {
        return $this->enableMarkdown;
    }

    public function setIssueNavigation(bool $enable): self
    {
        $this->issueNavigation = $enable;

        return $this;
    }

    public function isIssueNavigationEnabled(): bool
    {
        return $this->issueNavigation;
    }
}
