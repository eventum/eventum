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
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Table(name="user", uniqueConstraints={@ORM\UniqueConstraint(name="usr_email", columns={"usr_email"})})
 * @ORM\Entity(repositoryClass="Eventum\Model\Repository\UserRepository")
 */
class User implements UserInterface
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PENDING = 'pending';

    /**
     * @var int
     * @ORM\Column(name="usr_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="usr_customer_id", type="string", length=128, nullable=true)
     */
    private $customerId;

    /**
     * @var string
     * @ORM\Column(name="usr_customer_contact_id", type="string", length=128, nullable=true)
     */
    private $customerContactId;

    /**
     * @var DateTime
     * @ORM\Column(name="usr_created_date", type="datetime", nullable=false)
     */
    private $createdDate;

    /**
     * @var string
     * @ORM\Column(name="usr_status", type="string", length=8, nullable=false)
     */
    private $status;

    /**
     * @var string
     * @ORM\Column(name="usr_password", type="string", length=255, nullable=false)
     */
    private $password;

    /**
     * @var string
     * @ORM\Column(name="usr_full_name", type="string", length=255, nullable=false)
     */
    private $fullName;

    /**
     * @var string
     * @ORM\Column(name="usr_email", type="string", length=255, nullable=false)
     */
    private $email;

    /**
     * @var string
     * @ORM\Column(name="usr_sms_email", type="string", length=255, nullable=true)
     */
    private $smsEmail;

    /**
     * @var bool
     * @ORM\Column(name="usr_clocked_in", type="boolean", nullable=true)
     */
    private $clockedIn;

    /**
     * @var string
     * @ORM\Column(name="usr_lang", type="string", length=5, nullable=true)
     */
    private $language;

    /**
     * @var string
     * @ORM\Column(name="usr_external_id", type="string", length=100, nullable=false)
     */
    private $externalId;

    /**
     * @var DateTime
     * @ORM\Column(name="usr_last_login", type="datetime", nullable=true)
     */
    private $lastLogin;

    /**
     * @var DateTime
     * @ORM\Column(name="usr_last_failed_login", type="datetime", nullable=true)
     */
    private $lastFailedLogin;

    /**
     * @var int
     * @ORM\Column(name="usr_failed_logins", type="integer", nullable=false)
     */
    private $failedLogins;

    /**
     * @var string
     * @ORM\Column(name="usr_par_code", type="string", length=30, nullable=true)
     */
    private $partnerCode;

    public function getId(): int
    {
        return $this->id;
    }

    public function setCustomerId(?string $customerId): self
    {
        $this->customerId = $customerId;

        return $this;
    }

    public function getCustomerId(): ?string
    {
        return $this->customerId;
    }

    public function setCustomerContactId(?string $customerContactId): self
    {
        $this->customerContactId = $customerContactId;

        return $this;
    }

    public function getCustomerContactId(): ?string
    {
        return $this->customerContactId;
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

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
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

    public function setFullName(string $fullName): self
    {
        $this->fullName = $fullName;

        return $this;
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function setEmail(string $email): string
    {
        $this->email = $email;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setSmsEmail(?string $smsEmail): self
    {
        $this->smsEmail = $smsEmail;

        return $this;
    }

    public function getSmsEmail(): ?string
    {
        return $this->smsEmail;
    }

    public function setClockedIn(bool $clockedIn): self
    {
        $this->clockedIn = $clockedIn;

        return $this;
    }

    public function getClockedIn(): bool
    {
        return $this->clockedIn;
    }

    public function setLanguage(string $language): self
    {
        $this->language = $language;

        return $this;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function setExternalId(?string $externalId): self
    {
        $this->externalId = $externalId;

        return $this;
    }

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    public function setLastLogin(?DateTime $lastLogin): self
    {
        $this->lastLogin = $lastLogin;

        return $this;
    }

    public function getLastLogin(): ?DateTime
    {
        return $this->lastLogin;
    }

    public function setLastFailedLogin(?DateTime $lastFailedLogin): self
    {
        $this->lastFailedLogin = $lastFailedLogin;

        return $this;
    }

    public function getLastFailedLogin(): ?DateTime
    {
        return $this->lastFailedLogin;
    }

    public function setFailedLogins(int $failedLogins): self
    {
        $this->failedLogins = $failedLogins;

        return $this;
    }

    public function getFailedLogins(): int
    {
        return $this->failedLogins;
    }

    public function setPartnerCode(?string $partnerCode): self
    {
        $this->partnerCode = $partnerCode;

        return $this;
    }

    public function getPartnerCode(): ?string
    {
        return $this->partnerCode;
    }

    /**
     * Method to check whether an user is pending its confirmation
     * or not.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Method to check whether an user is active or not.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function getRoles(): array
    {
        // guarantee every user at least has ROLE_USER
        return [
            'ROLE_USER',
        ];
    }

    /**
     * Returns the salt that was originally used to encode the password.
     *
     * This can return null if the password was not encoded using a salt.
     *
     * @return string|null The salt
     */
    public function getSalt(): string
    {
        return $this->password;
    }

    /**
     * Returns the username used to authenticate the user.
     *
     * @return string The username
     */
    public function getUsername(): string
    {
        return $this->email;
    }

    public function eraseCredentials(): void
    {
    }
}
