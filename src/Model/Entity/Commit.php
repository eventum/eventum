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
class Commit extends BaseModel
{
    /**
     * @var integer
     */
    protected $com_id;

    /**
     * @var string
     */
    protected $com_scm_name;

    /**
     * @var string
     */
    protected $com_commit_id;

    /**
     * @var string
     */
    protected $com_author_email;

    /**
     * @var string
     */
    protected $com_author_name;

    /**
     * @var \DateTime
     */
    protected $com_commit_date;

    /**
     * @var string
     */
    protected $com_message;

    /**
     * @param int $id
     * @return $this
     */
    public function setId($id)
    {
        $this->com_id = $id;

        return $this;
    }

    /**
     * Get comId
     *
     * @return integer
     */
    public function getId()
    {
        return $this->com_id;
    }

    /**
     * Set comScmName
     *
     * @param string $scmName
     * @return Commit
     */
    public function setScmName($scmName)
    {
        $this->com_scm_name = $scmName;

        return $this;
    }

    /**
     * Get comScmName
     *
     * @return string
     */
    public function getScmName()
    {
        return $this->com_scm_name;
    }

    /**
     * Set comCommitId
     *
     * @param string $commitId
     * @return Commit
     */
    public function setCommitId($commitId)
    {
        $this->com_commit_id = $commitId;

        return $this;
    }

    /**
     * Get comCommitId
     *
     * @return string
     */
    public function getCommitId()
    {
        return $this->com_commit_id;
    }

    /**
     * Set comAuthorEmail
     *
     * @param string $authorEmail
     * @return Commit
     */
    public function setAuthorEmail($authorEmail)
    {
        $this->com_author_email = $authorEmail;

        return $this;
    }

    /**
     * Get comAuthorEmail
     *
     * @return string
     */
    public function getAuthorEmail()
    {
        return $this->com_author_email;
    }

    /**
     * Set comAuthorName
     *
     * @param string $authorName
     * @return Commit
     */
    public function setAuthorName($authorName)
    {
        $this->com_author_name = $authorName;

        return $this;
    }

    /**
     * Get comAuthorName
     *
     * @return string
     */
    public function getAuthorName()
    {
        return $this->com_author_name;
    }

    /**
     * Set comCommitDate
     *
     * @param \DateTime $commitDate
     * @return Commit
     */
    public function setCommitDate($commitDate)
    {
        $this->com_commit_date = $commitDate;

        return $this;
    }

    /**
     * Get comCommitDate
     *
     * @return \DateTime
     */
    public function getCommitDate()
    {
        return $this->com_commit_date;
    }

    /**
     * Set comMessage
     *
     * @param string $message
     * @return Commit
     */
    public function setMessage($message)
    {
        $this->com_message = $message;

        return $this;
    }

    /**
     * Get comMessage
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->com_message;
    }

    /**
     * @param string $commitId
     * @return $this
     */
    public function findOneByCommitId($commitId) {
        $res = $this->findAllByConditions(array('com_commit_id' => $commitId), 1);
        return $res ? $res[0] : null;
    }
}
