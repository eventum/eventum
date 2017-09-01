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
 * @Table(name="user", uniqueConstraints={@UniqueConstraint(name="usr_email", columns={"usr_email"})})
 * @Entity(repositoryClass="Eventum\Model\Repository\UserRepository")
 */
class User
{
    /**
     * @var int
     *
     * @Column(name="usr_id", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     * @Column(name="usr_customer_id", type="string", length=128, nullable=true)
     */
    private $customerId;

    /**
     * @var string
     * @Column(name="usr_customer_contact_id", type="string", length=128, nullable=true)
     */
    private $customerContactId;

    /**
     * @var \DateTime
     * @Column(name="usr_created_date", type="datetime", nullable=false)
     */
    private $createdDate;

    /**
     * @var string
     * @Column(name="usr_status", type="string", length=8, nullable=false)
     */
    private $status;

    /**
     * @var string
     * @Column(name="usr_password", type="string", length=255, nullable=false)
     */
    private $password;

    /**
     * @var string
     * @Column(name="usr_full_name", type="string", length=255, nullable=false)
     */
    private $fullName;

    /**
     * @var string
     * @Column(name="usr_email", type="string", length=255, nullable=false)
     */
    private $email;

    /**
     * @var string
     * @Column(name="usr_sms_email", type="string", length=255, nullable=true)
     */
    private $smsEmail;

    /**
     * @var bool
     * @Column(name="usr_clocked_in", type="boolean", nullable=true)
     */
    private $clockedIn;

    /**
     * @var string
     * @Column(name="usr_lang", type="string", length=5, nullable=true)
     */
    private $language;

    /**
     * @var string
     * @Column(name="usr_external_id", type="string", length=100, nullable=false)
     */
    private $externalId;

    /**
     * @var \DateTime
     * @Column(name="usr_last_login", type="datetime", nullable=true)
     */
    private $lastLogin;

    /**
     * @var \DateTime
     * @Column(name="usr_last_failed_login", type="datetime", nullable=true)
     */
    private $lastFailedLogin;

    /**
     * @var int
     * @Column(name="usr_failed_logins", type="integer", nullable=false)
     */
    private $failedLogins;

    /**
     * @var string
     * @Column(name="usr_par_code", type="string", length=30, nullable=true)
     */
    private $partnerCode;

    /**
     * Get user Id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set customerId
     *
     * @param string $customerId
     * @return User
     */
    public function setCustomerId($customerId)
    {
        $this->customerId = $customerId;

        return $this;
    }

    /**
     * Get usrCustomerId
     *
     * @return string
     */
    public function getCustomerId()
    {
        return $this->customerId;
    }

    /**
     * Set usrCustomerContactId
     *
     * @param string $customerContactId
     * @return User
     */
    public function setCustomerContactId($customerContactId)
    {
        $this->customerContactId = $customerContactId;

        return $this;
    }

    /**
     * Get usrCustomerContactId
     *
     * @return string
     */
    public function getCustomerContactId()
    {
        return $this->customerContactId;
    }

    /**
     * Set usrCreatedDate
     *
     * @param \DateTime $createdDate
     * @return User
     */
    public function setCreatedDate($createdDate)
    {
        $this->createdDate = $createdDate;

        return $this;
    }

    /**
     * Get usrCreatedDate
     *
     * @return \DateTime
     */
    public function getCreatedDate()
    {
        return $this->createdDate;
    }

    /**
     * Set usrStatus
     *
     * @param string $status
     * @return User
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get usrStatus
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set usrPassword
     *
     * @param string $password
     * @return User
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get usrPassword
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set usrFullName
     *
     * @param string $fullName
     * @return User
     */
    public function setFullName($fullName)
    {
        $this->fullName = $fullName;

        return $this;
    }

    /**
     * Get usrFullName
     *
     * @return string
     */
    public function getFullName()
    {
        return $this->fullName;
    }

    /**
     * Set usrEmail
     *
     * @param string $email
     * @return User
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get usrEmail
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set usrSmsEmail
     *
     * @param string $smsEmail
     * @return User
     */
    public function setSmsEmail($smsEmail)
    {
        $this->smsEmail = $smsEmail;

        return $this;
    }

    /**
     * Get usrSmsEmail
     *
     * @return string
     */
    public function getSmsEmail()
    {
        return $this->smsEmail;
    }

    /**
     * Set usrClockedIn
     *
     * @param bool $clockedIn
     * @return User
     */
    public function setClockedIn($clockedIn)
    {
        $this->clockedIn = $clockedIn;

        return $this;
    }

    /**
     * Get usrClockedIn
     *
     * @return bool
     */
    public function getClockedIn()
    {
        return $this->clockedIn;
    }

    /**
     * Set usrLang
     *
     * @param string $language
     * @return User
     */
    public function setLanguage($language)
    {
        $this->language = $language;

        return $this;
    }

    /**
     * Get usrLang
     *
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Set usrExternalId
     *
     * @param string $externalId
     * @return User
     */
    public function setExternalId($externalId)
    {
        $this->externalId = $externalId;

        return $this;
    }

    /**
     * Get usrExternalId
     *
     * @return string
     */
    public function getExternalId()
    {
        return $this->externalId;
    }

    /**
     * Set usrLastLogin
     *
     * @param \DateTime $lastLogin
     * @return User
     */
    public function setLastLogin($lastLogin)
    {
        $this->lastLogin = $lastLogin;

        return $this;
    }

    /**
     * Get usrLastLogin
     *
     * @return \DateTime
     */
    public function getLastLogin()
    {
        return $this->lastLogin;
    }

    /**
     * Set usrLastFailedLogin
     *
     * @param \DateTime $lastFailedLogin
     * @return User
     */
    public function setLastFailedLogin($lastFailedLogin)
    {
        $this->lastFailedLogin = $lastFailedLogin;

        return $this;
    }

    /**
     * Get usrLastFailedLogin
     *
     * @return \DateTime
     */
    public function getLastFailedLogin()
    {
        return $this->lastFailedLogin;
    }

    /**
     * Set usrFailedLogins
     *
     * @param int $failedLogins
     * @return User
     */
    public function setFailedLogins($failedLogins)
    {
        $this->failedLogins = $failedLogins;

        return $this;
    }

    /**
     * Get usrFailedLogins
     *
     * @return int
     */
    public function getFailedLogins()
    {
        return $this->failedLogins;
    }

    /**
     * Set usrParCode
     *
     * @param string $partnerCode
     * @return User
     */
    public function setPartnerCode($partnerCode)
    {
        $this->partnerCode = $partnerCode;

        return $this;
    }

    /**
     * Get usrParCode
     *
     * @return string
     */
    public function getPartnerCode()
    {
        return $this->partnerCode;
    }
}
