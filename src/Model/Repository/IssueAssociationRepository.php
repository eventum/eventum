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
namespace Eventum\Model\Repository;

use DB_Helper;
use Eventum\Db\DatabaseException;
use Eventum\Model\Entity;
use History;
use InvalidArgumentException;
use LogicException;
use User;

class IssueAssociationRepository extends BaseRepository
{
    /**
     * Method used to get the list of issues associated to a specific issue.
     *
     * @param   integer $issue_id The issue ID
     * @return  array The list of associated issues
     */
    public function getAssociatedIssues($issue_id)
    {
        $res = [];
        $isa = Entity\IssueAssociation::create()->findByIssueId($issue_id);
        if (!$isa) {
            return $res;
        }

        foreach ($isa as $ia) {
            // check which column to use
            if ($ia->getIssueId() == $issue_id) {
                $iss_id = $ia->getAssociatedId();
            } else {
                $iss_id = $ia->getIssueId();
            }

            // can't be itself!
            if ($iss_id == $issue_id) {
                throw new LogicException();
                continue;
            }

            $res[] = $iss_id;
        }

        // make unique
        $res = array_unique($res);

        // and sort
        asort($res);

        return $res;
    }

    /**
     * Method used to associate an existing issue with another one.
     *
     * @param   integer $issue_id The issue ID
     * @param   integer $associated_issue_id The other issue ID
     */
    public function addIssueAssociation($usr_id, $issue_id, $associated_issue_id)
    {
        // see if there already is association
        $assoc = $this->getAssociatedIssues($issue_id);
        if (in_array($associated_issue_id, $assoc)) {
            throw new InvalidArgumentException("Issue $issue_id already associated to $associated_issue_id");
        }

        Entity\IssueAssociation::create()
            ->setIssueId($issue_id)
            ->setAssociatedId($associated_issue_id)
            ->save();

        History::add(
            $issue_id, $usr_id, 'issue_associated', 'Issue associated to Issue #{associated_id} by {user}', [
                'associated_id' => $associated_issue_id,
                'user' => User::getFullName($usr_id)
            ]
        );
    }
}
