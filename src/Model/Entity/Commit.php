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
 * Commit
 */
class Commit
{
    /**
     * @var integer
     */
    private $comId;

    /**
     * @var string
     */
    private $comScmName;

    /**
     * @var string
     */
    private $comCommitId;

    /**
     * @var string
     */
    private $comAuthorEmail;

    /**
     * @var string
     */
    private $comAuthorName;

    /**
     * @var \DateTime
     */
    private $comCommitDate;

    /**
     * @var string
     */
    private $comMessage;

    /**
     * Get comId
     *
     * @return integer
     */
    public function getComId()
    {
        return $this->comId;
    }

    /**
     * Set comScmName
     *
     * @param string $comScmName
     * @return Commit
     */
    public function setComScmName($comScmName)
    {
        $this->comScmName = $comScmName;

        return $this;
    }

    /**
     * Get comScmName
     *
     * @return string
     */
    public function getComScmName()
    {
        return $this->comScmName;
    }

    /**
     * Set comCommitId
     *
     * @param string $comCommitId
     * @return Commit
     */
    public function setComCommitId($comCommitId)
    {
        $this->comCommitId = $comCommitId;

        return $this;
    }

    /**
     * Get comCommitId
     *
     * @return string
     */
    public function getComCommitId()
    {
        return $this->comCommitId;
    }

    /**
     * Set comAuthorEmail
     *
     * @param string $comAuthorEmail
     * @return Commit
     */
    public function setComAuthorEmail($comAuthorEmail)
    {
        $this->comAuthorEmail = $comAuthorEmail;

        return $this;
    }

    /**
     * Get comAuthorEmail
     *
     * @return string
     */
    public function getComAuthorEmail()
    {
        return $this->comAuthorEmail;
    }

    /**
     * Set comAuthorName
     *
     * @param string $comAuthorName
     * @return Commit
     */
    public function setComAuthorName($comAuthorName)
    {
        $this->comAuthorName = $comAuthorName;

        return $this;
    }

    /**
     * Get comAuthorName
     *
     * @return string
     */
    public function getComAuthorName()
    {
        return $this->comAuthorName;
    }

    /**
     * Set comCommitDate
     *
     * @param \DateTime $comCommitDate
     * @return Commit
     */
    public function setComCommitDate($comCommitDate)
    {
        $this->comCommitDate = $comCommitDate;

        return $this;
    }

    /**
     * Get comCommitDate
     *
     * @return \DateTime
     */
    public function getComCommitDate()
    {
        return $this->comCommitDate;
    }

    /**
     * Set comMessage
     *
     * @param string $comMessage
     * @return Commit
     */
    public function setComMessage($comMessage)
    {
        $this->comMessage = $comMessage;

        return $this;
    }

    /**
     * Get comMessage
     *
     * @return string
     */
    public function getComMessage()
    {
        return $this->comMessage;
    }
}