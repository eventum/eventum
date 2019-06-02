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

namespace Eventum\Export\Model;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Id;

class Issue
{
    /**
     * @var int Issue iid
     * @Column(name="Issue ID", type="integer")
     * @Id
     */
    public $issueId;

    /**
     * @var string A link to the issue on GitLab
     * @Column(name="URL", type="string")
     */
    public $url;

    /**
     * @var string Issue title
     * @Column(name="Title", type="string")
     */
    public $title;

    /**
     * @var string Open or Closed
     * @Column(name="State", type="string")
     */
    public $state;

    /**
     * @var string Issue description
     * @Column(name="Description", type="string")
     */
    public $description;

    /**
     * @var string Full name of the issue author
     * @Column(name="Author", type="string")
     */
    public $author;

    /**
     * @var string Username of the author, with the @ symbol omitted
     * @Column(name="Author Username", type="string")
     */
    public $authorUsername;

    /**
     * @var string Full name of the issue assignee
     * @Column(name="Assignee", type="string")
     */
    public $assignee;

    /**
     * @var string Username of the author, with the @ symbol omitted
     * @Column(name="Assignee Username", type="string")
     */
    public $assigneeUsername;

    /**
     * @var string Yes or No
     * @Column(name="Confidential", type="string")
     */
    public $confidential;

    /**
     * @var string Yes or No
     * @Column(name="Locked", type="string")
     */
    public $locked;

    /**
     * @var string Formated as YYYY-MM-DD
     * @Column(name="Due Date", type="string")
     */
    public $dueDate;

    /**
     * @var string Formated as YYYY-MM-DD HH:MM:SS
     * @Column(name="Created At (UTC)", type="string")
     */
    public $createdAt;

    /**
     * @var string Formated as YYYY-MM-DD HH:MM:SS
     * @Column(name="Updated At (UTC)", type="string")
     */
    public $updatedAt;

    /**
     * @var string Title of the issue milestone
     * @Column(name="Milestone", type="string")
     */
    public $milestone;

    /**
     * @var string Issue weight
     * @Column(name="Weight", type="string")
     */
    public $weight;

    /**
     * @var string Title of any labels joined with a comma (",")
     * @Column(name="Labels", type="string")
     */
    public $labels;

    /**
     * @var string Time estimate in seconds
     * @Column(name="Time Estimate", type="string")
     */
    public $timeEstimate;

    /**
     * @var string Time spent in seconds
     * @Column(name="Time Spent", type="string")
     */
    public $timeSpent;
}
