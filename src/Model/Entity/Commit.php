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
 * @Entity @Table(name="commit")
 **/
class Commit extends BaseModel
{
    /** @Id @Column(type="integer") @GeneratedValue */
    protected $com_id;

    /**
     * @var string
     *
     * @Column(type="string", length=255, nullable=false)
     */
    protected $com_scm_name;

    /**
     * @var string
     *
     * @Column(type="string", length=255, nullable=true)
     */
    protected $com_project_name;

    /**
     * @var string
     *
     * @Column(type="string", length=40, nullable=false)
     */
    protected $com_changeset;

    /**
     * @var string
     *
     * @Column(type="string", length=255, nullable=true)
     */
    protected $com_branch;

    /**
     * @var int
     *
     * @Column(type="integer", nullable=true)
     */
    protected $com_usr_id;

    /**
     * @var string
     *
     * @Column(type="string", length=255, nullable=true)
     */
    protected $com_author_email;

    /**
     * @var string
     *
     * @Column(type="string", length=255, nullable=true)
     */
    protected $com_author_name;

    /**
     * @var \DateTime
     *
     * @Column(type="datetime", nullable=false)
     */
    protected $com_commit_date;

    /**
     * @var string
     *
     * @Column(type="text", length=16777215, nullable=true)
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
     * @return int
     */
    public function getId()
    {
        return $this->com_id;
    }

    /**
     * Set SCM Name
     *
     * @param string $scmName
     * @return $this
     */
    public function setScmName($scmName)
    {
        $this->com_scm_name = $scmName;

        return $this;
    }

    /**
     * Get SCM Name
     *
     * @return string
     */
    public function getScmName()
    {
        return $this->com_scm_name;
    }

    /**
     * Set SCM ProjectName
     *
     * @param string $projectName
     * @return $this
     */
    public function setProjectName($projectName)
    {
        $this->com_project_name = $projectName;

        return $this;
    }

    /**
     * Get SCM ProjectName
     *
     * @return string
     */
    public function getProjectName()
    {
        return $this->com_project_name;
    }

    /**
     * Set changeset
     *
     * @param string $changeset
     * @return $this
     */
    public function setChangeset($changeset)
    {
        $this->com_changeset = $changeset;

        return $this;
    }

    /**
     * Get changeset
     *
     * @param bool $short
     * @return string
     */
    public function getChangeset($short = false)
    {
        // truncate if it's longer than 16 (cvs commitid)
        if ($short && strlen($this->com_changeset) > 16) {
            return substr($this->com_changeset, 0, 7);
        }

        return $this->com_changeset;
    }

    /**
     * Set SCM branch
     *
     * @param string $branch
     * @return $this
     */
    public function setBranch($branch)
    {
        $this->com_branch = $branch;

        return $this;
    }

    /**
     * Get SCM branch
     *
     * @return string
     */
    public function getBranch()
    {
        return $this->com_branch;
    }

    /**
     * Get Eventum User Id
     *
     * @param int $usr_id
     * @return $this
     */
    public function setUserId($usr_id)
    {
        $this->com_usr_id = $usr_id;

        return $this;
    }

    /**
     * Get Eventum User Id
     *
     * @return string
     */
    public function getUserId()
    {
        return $this->com_usr_id;
    }

    /**
     * Set comAuthorEmail
     *
     * @param string $authorEmail
     * @return $this
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
     * @return $this
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
     * @return $this
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
     * @return $this
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

    /** @var CommitFile[] */
    private $files = [];

    public function addFile(CommitFile $cf)
    {
        $this->files[] = $cf;
    }

    /**
     * @return CommitFile[]
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * Get formatted author name, combining name and email
     */
    public function getAuthor()
    {
        $name = $this->getAuthorName();
        $email = $this->getAuthorEmail();

        if (!$email) {
            return $name;
        }

        return "$name <$email>";
    }

    /**
     * @param string $changeset
     * @return Commit
     */
    public function findOneByChangeset($changeset)
    {
        $res = $this->findAllByConditions(['com_changeset' => $changeset], 1);

        return $res ? $res[0] : null;
    }

    /**
     * @param int $id
     * @return Commit
     */
    public function findById($id)
    {
        $res = $this->findAllByConditions(['com_id' => $id], 1);

        return $res ? $res[0] : null;
    }

    /**
     * @return CommitRepo
     */
    public function getCommitRepo()
    {
        return new CommitRepo($this->getScmName());
    }
}
