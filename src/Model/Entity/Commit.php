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
    private $id;

    /**
     * @var string
     */
    private $scmName;

    /**
     * @var string
     */
    private $commitId;

    /**
     * @var string
     */
    private $authorEmail;

    /**
     * @var string
     */
    private $authorName;

    /**
     * @var \DateTime
     */
    private $commitDate;

    /**
     * @var string
     */
    private $message;

    /**
     * Get comId
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set comScmName
     *
     * @param string $scmName
     * @return Commit
     */
    public function setScmName($scmName)
    {
        $this->scmName = $scmName;

        return $this;
    }

    /**
     * Get comScmName
     *
     * @return string
     */
    public function getScmName()
    {
        return $this->scmName;
    }

    /**
     * Set comCommitId
     *
     * @param string $commitId
     * @return Commit
     */
    public function setCommitId($commitId)
    {
        $this->commitId = $commitId;

        return $this;
    }

    /**
     * Get comCommitId
     *
     * @return string
     */
    public function getCommitId()
    {
        return $this->commitId;
    }

    /**
     * Set comAuthorEmail
     *
     * @param string $authorEmail
     * @return Commit
     */
    public function setAuthorEmail($authorEmail)
    {
        $this->authorEmail = $authorEmail;

        return $this;
    }

    /**
     * Get comAuthorEmail
     *
     * @return string
     */
    public function getAuthorEmail()
    {
        return $this->authorEmail;
    }

    /**
     * Set comAuthorName
     *
     * @param string $authorName
     * @return Commit
     */
    public function setAuthorName($authorName)
    {
        $this->authorName = $authorName;

        return $this;
    }

    /**
     * Get comAuthorName
     *
     * @return string
     */
    public function getAuthorName()
    {
        return $this->authorName;
    }

    /**
     * Set comCommitDate
     *
     * @param \DateTime $commitDate
     * @return Commit
     */
    public function setCommitDate($commitDate)
    {
        $this->commitDate = $commitDate;

        return $this;
    }

    /**
     * Get comCommitDate
     *
     * @return \DateTime
     */
    public function getCommitDate()
    {
        return $this->commitDate;
    }

    /**
     * Set comMessage
     *
     * @param string $message
     * @return Commit
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get comMessage
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }
}