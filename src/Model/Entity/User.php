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
 * User
 *
 * @ORM\Table(name="user", uniqueConstraints={@ORM\UniqueConstraint(name="usr_email", columns={"usr_email"})})
 * @ORM\Entity
 */
class User
{
    /**
     * @var int
     *
     * @ORM\Column(name="usr_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $usrId;

    /**
     * @var string
     *
     * @ORM\Column(name="usr_customer_id", type="string", length=128, nullable=true)
     */
    private $usrCustomerId;

    /**
     * @var string
     *
     * @ORM\Column(name="usr_customer_contact_id", type="string", length=128, nullable=true)
     */
    private $usrCustomerContactId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="usr_created_date", type="datetime", nullable=false)
     */
    private $usrCreatedDate;

    /**
     * @var string
     *
     * @ORM\Column(name="usr_status", type="string", length=8, nullable=false)
     */
    private $usrStatus;

    /**
     * @var string
     *
     * @ORM\Column(name="usr_password", type="string", length=255, nullable=false)
     */
    private $usrPassword;

    /**
     * @var string
     *
     * @ORM\Column(name="usr_full_name", type="string", length=255, nullable=false)
     */
    private $usrFullName;

    /**
     * @var string
     *
     * @ORM\Column(name="usr_email", type="string", length=255, nullable=false)
     */
    private $usrEmail;

    /**
     * @var string
     *
     * @ORM\Column(name="usr_sms_email", type="string", length=255, nullable=true)
     */
    private $usrSmsEmail;

    /**
     * @var bool
     *
     * @ORM\Column(name="usr_clocked_in", type="boolean", nullable=true)
     */
    private $usrClockedIn;

    /**
     * @var string
     *
     * @ORM\Column(name="usr_lang", type="string", length=5, nullable=true)
     */
    private $usrLang;

    /**
     * @var string
     *
     * @ORM\Column(name="usr_external_id", type="string", length=100, nullable=false)
     */
    private $usrExternalId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="usr_last_login", type="datetime", nullable=true)
     */
    private $usrLastLogin;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="usr_last_failed_login", type="datetime", nullable=true)
     */
    private $usrLastFailedLogin;

    /**
     * @var int
     *
     * @ORM\Column(name="usr_failed_logins", type="integer", nullable=false)
     */
    private $usrFailedLogins;

    /**
     * @var string
     *
     * @ORM\Column(name="usr_par_code", type="string", length=30, nullable=true)
     */
    private $usrParCode;

    /**
     * Get usrId
     *
     * @return int
     */
    public function getUsrId()
    {
        return $this->usrId;
    }

    /**
     * Set usrCustomerId
     *
     * @param string $usrCustomerId
     * @return User
     */
    public function setUsrCustomerId($usrCustomerId)
    {
        $this->usrCustomerId = $usrCustomerId;

        return $this;
    }

    /**
     * Get usrCustomerId
     *
     * @return string
     */
    public function getUsrCustomerId()
    {
        return $this->usrCustomerId;
    }

    /**
     * Set usrCustomerContactId
     *
     * @param string $usrCustomerContactId
     * @return User
     */
    public function setUsrCustomerContactId($usrCustomerContactId)
    {
        $this->usrCustomerContactId = $usrCustomerContactId;

        return $this;
    }

    /**
     * Get usrCustomerContactId
     *
     * @return string
     */
    public function getUsrCustomerContactId()
    {
        return $this->usrCustomerContactId;
    }

    /**
     * Set usrCreatedDate
     *
     * @param \DateTime $usrCreatedDate
     * @return User
     */
    public function setUsrCreatedDate($usrCreatedDate)
    {
        $this->usrCreatedDate = $usrCreatedDate;

        return $this;
    }

    /**
     * Get usrCreatedDate
     *
     * @return \DateTime
     */
    public function getUsrCreatedDate()
    {
        return $this->usrCreatedDate;
    }

    /**
     * Set usrStatus
     *
     * @param string $usrStatus
     * @return User
     */
    public function setUsrStatus($usrStatus)
    {
        $this->usrStatus = $usrStatus;

        return $this;
    }

    /**
     * Get usrStatus
     *
     * @return string
     */
    public function getUsrStatus()
    {
        return $this->usrStatus;
    }

    /**
     * Set usrPassword
     *
     * @param string $usrPassword
     * @return User
     */
    public function setUsrPassword($usrPassword)
    {
        $this->usrPassword = $usrPassword;

        return $this;
    }

    /**
     * Get usrPassword
     *
     * @return string
     */
    public function getUsrPassword()
    {
        return $this->usrPassword;
    }

    /**
     * Set usrFullName
     *
     * @param string $usrFullName
     * @return User
     */
    public function setUsrFullName($usrFullName)
    {
        $this->usrFullName = $usrFullName;

        return $this;
    }

    /**
     * Get usrFullName
     *
     * @return string
     */
    public function getUsrFullName()
    {
        return $this->usrFullName;
    }

    /**
     * Set usrEmail
     *
     * @param string $usrEmail
     * @return User
     */
    public function setUsrEmail($usrEmail)
    {
        $this->usrEmail = $usrEmail;

        return $this;
    }

    /**
     * Get usrEmail
     *
     * @return string
     */
    public function getUsrEmail()
    {
        return $this->usrEmail;
    }

    /**
     * Set usrSmsEmail
     *
     * @param string $usrSmsEmail
     * @return User
     */
    public function setUsrSmsEmail($usrSmsEmail)
    {
        $this->usrSmsEmail = $usrSmsEmail;

        return $this;
    }

    /**
     * Get usrSmsEmail
     *
     * @return string
     */
    public function getUsrSmsEmail()
    {
        return $this->usrSmsEmail;
    }

    /**
     * Set usrClockedIn
     *
     * @param bool $usrClockedIn
     * @return User
     */
    public function setUsrClockedIn($usrClockedIn)
    {
        $this->usrClockedIn = $usrClockedIn;

        return $this;
    }

    /**
     * Get usrClockedIn
     *
     * @return bool
     */
    public function getUsrClockedIn()
    {
        return $this->usrClockedIn;
    }

    /**
     * Set usrLang
     *
     * @param string $usrLang
     * @return User
     */
    public function setUsrLang($usrLang)
    {
        $this->usrLang = $usrLang;

        return $this;
    }

    /**
     * Get usrLang
     *
     * @return string
     */
    public function getUsrLang()
    {
        return $this->usrLang;
    }

    /**
     * Set usrExternalId
     *
     * @param string $usrExternalId
     * @return User
     */
    public function setUsrExternalId($usrExternalId)
    {
        $this->usrExternalId = $usrExternalId;

        return $this;
    }

    /**
     * Get usrExternalId
     *
     * @return string
     */
    public function getUsrExternalId()
    {
        return $this->usrExternalId;
    }

    /**
     * Set usrLastLogin
     *
     * @param \DateTime $usrLastLogin
     * @return User
     */
    public function setUsrLastLogin($usrLastLogin)
    {
        $this->usrLastLogin = $usrLastLogin;

        return $this;
    }

    /**
     * Get usrLastLogin
     *
     * @return \DateTime
     */
    public function getUsrLastLogin()
    {
        return $this->usrLastLogin;
    }

    /**
     * Set usrLastFailedLogin
     *
     * @param \DateTime $usrLastFailedLogin
     * @return User
     */
    public function setUsrLastFailedLogin($usrLastFailedLogin)
    {
        $this->usrLastFailedLogin = $usrLastFailedLogin;

        return $this;
    }

    /**
     * Get usrLastFailedLogin
     *
     * @return \DateTime
     */
    public function getUsrLastFailedLogin()
    {
        return $this->usrLastFailedLogin;
    }

    /**
     * Set usrFailedLogins
     *
     * @param int $usrFailedLogins
     * @return User
     */
    public function setUsrFailedLogins($usrFailedLogins)
    {
        $this->usrFailedLogins = $usrFailedLogins;

        return $this;
    }

    /**
     * Get usrFailedLogins
     *
     * @return int
     */
    public function getUsrFailedLogins()
    {
        return $this->usrFailedLogins;
    }

    /**
     * Set usrParCode
     *
     * @param string $usrParCode
     * @return User
     */
    public function setUsrParCode($usrParCode)
    {
        $this->usrParCode = $usrParCode;

        return $this;
    }

    /**
     * Get usrParCode
     *
     * @return string
     */
    public function getUsrParCode()
    {
        return $this->usrParCode;
    }
}
