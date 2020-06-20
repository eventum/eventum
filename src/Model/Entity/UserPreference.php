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
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Eventum\Model\Repository\Traits\GetOneTrait;
use Eventum\ServiceContainer;
use Setup;

/**
 * @ORM\Table(name="user_preference")
 * @ORM\Entity(repositoryClass="Eventum\Model\Repository\UserPreferenceRepository")
 */
class UserPreference
{
    use GetOneTrait;

    /**
     * @var int
     * @ORM\Column(name="upr_usr_id", type="integer", nullable=false)
     * @ORM\Id
     */
    private $userId;

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
     * Refresh rate, in minutes
     *
     * @var int
     * @ORM\Column(name="upr_list_refresh_rate", type="integer", nullable=false)
     */
    private $listRefreshRate;

    /**
     * Refresh rate, in minutes
     *
     * @var int
     * @ORM\Column(name="upr_email_refresh_rate", type="integer", nullable=false)
     */
    private $emailRefreshRate;

    /**
     * @var string
     * @ORM\Column(name="upr_email_signature", type="text", nullable=true)
     */
    private $emailSignature;

    /**
     * @var bool
     * @ORM\Column(name="upr_auto_append_email_sig", type="boolean", nullable=false)
     */
    private $autoAppendEmailSignature = false;

    /**
     * @var bool
     * @ORM\Column(name="upr_auto_append_note_sig", type="boolean", nullable=false)
     */
    private $autoAppendNoteSignature = false;

    /**
     * @var bool
     * @ORM\Column(name="upr_auto_close_popup_window", type="boolean", nullable=false)
     */
    private $autoClosePopupWindow = true;

    /**
     * @var bool
     * @ORM\Column(name="upr_relative_date", type="boolean", nullable=false)
     */
    private $relativeDate;

    /**
     * @var bool
     * @ORM\Column(name="upr_collapsed_emails", type="boolean", nullable=false)
     */
    private $collapsedEmails = true;

    /**
     * @var bool
     * @ORM\Column(name="upr_issue_navigation", type="boolean", nullable=false)
     */
    private $issueNavigation = false;

    /**
     * @var UserProjectPreference[]|PersistentCollection
     * @ORM\OneToMany(targetEntity="UserProjectPreference", mappedBy="userPreference", cascade={"ALL"}, indexBy="projectId")
     * @ORM\JoinColumn(name="id", referencedColumnName="upp_prj_id")
     */
    private $projects;

    public function __construct(int $usr_id)
    {
        $this->userId = $usr_id;
        $this->timezone = Setup::getDefaultTimezone();
        $this->weekFirstday = Setup::getDefaultWeekday();

        $config = ServiceContainer::getConfig();
        $this->relativeDate = $config['relative_date'] === 'enabled';
        $this->listRefreshRate = $config['default_refresh_rate'];
        $this->emailRefreshRate = $config['default_refresh_rate'];
    }

    public function setUserId(int $usr_id): self
    {
        $this->userId = $usr_id;

        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
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

    public function setEmailSignature(?string $signature): self
    {
        $this->emailSignature = $signature;

        return $this;
    }

    public function getEmailSignature(): ?string
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

    public function collapsedEmails(): bool
    {
        return $this->collapsedEmails;
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

    /**
     * @return UserProjectPreference[]|Collection
     */
    public function getProjects(): ?Collection
    {
        return $this->projects;
    }

    public function getProjectById(int $prj_id): ?UserProjectPreference
    {
        return $this->getOne($this->projects, 'projectId', '=', $prj_id);
    }

    public function findOrCreateProjectById(int $prj_id): UserProjectPreference
    {
        $upp = $this->getProjectById($prj_id);
        if (!$upp) {
            $upp = new UserProjectPreference($this, $prj_id);
        }

        return $upp;
    }
}
