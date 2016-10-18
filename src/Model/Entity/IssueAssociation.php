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
 * IssueAssociation
 *
 */
class IssueAssociation extends BaseModel
{
    /**
     * @var integer
     */
    protected $isa_issue_id;

    /**
     * @var integer
     */
    protected $isa_associated_id;

    public function setId($id)
    {
        // method ignored, needed for BaseModel save
    }

    /**
     * Set Issue Id
     *
     * @param integer $isa_issue_id
     * @return IssueAssociation
     */
    public function setIssueId($isa_issue_id)
    {
        $this->isa_issue_id = $isa_issue_id;

        return $this;
    }

    /**
     * Get Issue Id
     *
     * @return integer
     */
    public function getIssueId()
    {
        return $this->isa_issue_id;
    }

    /**
     * Set associated Issue Id
     *
     * @param integer $isa_associated_id
     * @return IssueAssociation
     */
    public function setAssociatedId($isa_associated_id)
    {
        $this->isa_associated_id = $isa_associated_id;

        return $this;
    }

    /**
     * Get associated Issue Id
     *
     * @return integer
     */
    public function getAssociatedId()
    {
        return $this->isa_associated_id;
    }

    /**
     * @param int $issue_id
     * @return $this[]
     */
    public function findByIssueId($issue_id)
    {
        $where = ['isa_issue_id' => $issue_id, 'isa_associated_id' => $issue_id];

        return $this->findAllByConditions($where, null, null, $conditionJoin = ' OR ');
    }

    public function removeAssociation($issue_id, $associated_id)
    {
        $query = '(isa_issue_id = ? AND isa_associated_id = ?) OR (isa_issue_id = ? AND isa_associated_id = ?)';
        $params = [
            $issue_id, $associated_id,
            $associated_id, $issue_id,
        ];
        $this->deleteByQuery($query, $params);
    }
}
