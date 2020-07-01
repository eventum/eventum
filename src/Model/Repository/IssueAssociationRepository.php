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
use Doctrine\ORM\EntityRepository;
use Ds\Set;
use Eventum\Model\Entity;
use History;
use InvalidArgumentException;
use Issue;
use Misc;
use PDO;
use User;

class IssueAssociationRepository extends EntityRepository
{
    /**
     * Method used to get the list of issues associated to a specific issue.
     *
     * @param int $issue_id The issue ID
     * @return int[] The list of associated issues
     */
    public function getAssociatedIssues($issue_id): array
    {
        // doctrine doesn't support UNION
        // and we want just single column, use PDO directly
        $em = $this->getEntityManager();
        $connection = $em->getConnection();
        $query = $connection->prepare(
            '
            SELECT isa_associated_id
            FROM issue_association
            WHERE isa_issue_id = :issue_id
            UNION
            SELECT isa_issue_id
            FROM issue_association
            WHERE isa_associated_id = :issue_id
        '
        );
        $query->execute([':issue_id' => $issue_id]);

        $set = new Set();
        while (($id = $query->fetchColumn()) !== false) {
            $set->add((int)$id);
        }

        $set->sort();

        return $set->toArray();
    }

    /**
     * Get issue status and title details for specified issues.
     *
     * TODO: this is not right place for this method,
     * it should be in IssueRepository, but that class does not exist yet
     *
     * @param int[] $issues
     * @return array limited details for issues
     */
    public function getIssueDetails($issues)
    {
        if (count($issues) < 1) {
            return [];
        }
        $stmt
            = 'SELECT
                    iss_id associated_issue,
                    iss_summary associated_title,
                    sta_title current_status,
                    sta_is_closed is_closed
                 FROM
                    `issue`,
                    `status`
                 WHERE
                    iss_sta_id=sta_id AND
                    iss_id IN (' . DB_Helper::buildList($issues) . ')';

        return DB_Helper::getInstance()->getAll($stmt, $issues);
    }

    /**
     * Method used to associate an existing issue with another one.
     *
     * @param int $usr_id User Id performing the operation
     * @param int $issue_id The issue ID
     * @param int $associated_issue_id The other issue ID
     */
    public function addIssueAssociation($usr_id, $issue_id, $associated_issue_id): void
    {
        // see if there already is association
        $assoc = $this->getAssociatedIssues($issue_id);
        if (in_array($associated_issue_id, $assoc)) {
            throw new InvalidArgumentException("Issue $issue_id already associated to $associated_issue_id");
        }

        $entity = new Entity\IssueAssociation();
        $entity
            ->setIssueId($issue_id)
            ->setAssociatedIssueId($associated_issue_id);
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        History::add(
            $issue_id,
            $usr_id,
            'issue_associated',
            'Issue associated to Issue #{associated_id} by {user}',
            [
                'associated_id' => $associated_issue_id,
                'user' => User::getFullName($usr_id),
            ]
        );
    }

    /**
     * Method used to remove an issue association from an issue.
     *
     * @param int $usr_id User Id performing the operation
     * @param int $issue_id The issue ID
     * @param int $associated_issue_id the associated issue ID to remove
     */
    public function removeAssociation($usr_id, $issue_id, $associated_issue_id): void
    {
        // see if there already is association
        $assoc = $this->getAssociatedIssues($issue_id);
        if (!in_array($associated_issue_id, $assoc)) {
            throw new InvalidArgumentException("Issue $issue_id not associated to $associated_issue_id");
        }

        $this->deleteByIssueAssociation($issue_id, $associated_issue_id);

        $full_name = User::getFullName($usr_id);
        $pairs = [
            [$issue_id, $associated_issue_id],
            [$associated_issue_id, $issue_id],
        ];

        foreach ($pairs as $pair) {
            [$issue_id, $associated_issue_id] = $pair;
            $params = [
                'issue_id' => $associated_issue_id,
                'user' => $full_name,
            ];
            History::add(
                $issue_id,
                $usr_id,
                'issue_unassociated',
                'Issue association to Issue #{issue_id} removed by {user}',
                $params
            );
        }
    }

    /**
     * Update the issue associations
     *
     * @param int $usr_id User Id performing the operation
     * @param int $issue_id issue_id to update associations
     * @param int[] $issues issue_id's to associate with
     * @return string[] errors from operation
     */
    public function updateAssociations($usr_id, $issue_id, $issues)
    {
        [$issues, $errors] = $this->filterExistingIssues($issues, $issue_id);
        $existing_associations = $this->getAssociatedIssues($issue_id);

        $add = array_diff($issues, $existing_associations);
        $remove = array_diff($existing_associations, $issues);
        if (!$add && !$remove) {
            return $errors;
        }

        foreach ($add as $associated_id) {
            $this->addIssueAssociation($usr_id, $issue_id, $associated_id);
        }
        foreach ($remove as $associated_id) {
            $this->removeAssociation($usr_id, $issue_id, $associated_id);
        }

        return $errors;
    }

    /**
     * @param int[] $issues
     * @internal used for tests
     */
    public function deleteAllRelations($issues): void
    {
        $this
            ->createQueryBuilder('q')
            ->delete(Entity\IssueAssociation::class, 'a')
            ->where('a.isa_issue_id IN (:issues) OR a.isa_associated_id IN (:issues)')
            ->setParameter('issues', $issues)
            ->getQuery()
            ->execute();
    }

    /**
     * @param int $issue_id
     * @param int $associated_issue_id
     * @internal used by removeAssociation
     */
    private function deleteByIssueAssociation($issue_id, $associated_issue_id): void
    {
        $qb = $this->createQueryBuilder('q');
        $qb->delete(Entity\IssueAssociation::class, 'a');

        $expr = $qb->expr();
        $left = $expr->andX('a.isa_issue_id = :isa_issue_id', 'a.isa_associated_id = :isa_associated_id');
        $right = $expr->andX('a.isa_issue_id = :isa_associated_id', 'a.isa_associated_id = :isa_issue_id');
        $qb->where(
            $expr->orX()
                ->add($left)
                ->add($right)
        );

        $qb->setParameter('isa_issue_id', $issue_id);
        $qb->setParameter('isa_associated_id', $associated_issue_id);
        $query = $qb->getQuery();
        $query->execute();
    }

    /**
     * Filter issues for invalid input and issues that do not exist.
     *
     * @param int[] $issues
     * @param int $issue_id current issue id to remove if present
     * @return array
     */
    private function filterExistingIssues($issues, $issue_id)
    {
        // make issues list unique by flipping the array
        // otherwise removing $issue_id from the list (using array_search) would remove only first occurrence
        $issues = array_flip(array_filter(Misc::trim($issues)));
        unset($issues[$issue_id]);

        $res = $errors = [];
        foreach (array_keys($issues) as $input) {
            $iss_id = (int)$input;
            if ($iss_id <= 0) {
                $errors[] = $this->getInvalidIssueError($input);

                continue;
            }
            if (!Issue::exists($iss_id, false)) {
                $errors[] = $this->getIssueRemovedError($iss_id);
                continue;
            }
            $res[] = $iss_id;
        }

        return [$res, $errors];
    }

    /**
     * @param int $issue_id
     * @return string
     */
    private function getInvalidIssueError($issue_id)
    {
        return ev_gettext(
            '"%s" was not valid Issue Id and was removed.',
            $issue_id
        );
    }

    /**
     * @param int $issue_id
     * @return string
     */
    private function getIssueRemovedError($issue_id)
    {
        return ev_gettext(
            'Issue #%s does not exist and was removed from the list of associated issues.',
            $issue_id
        );
    }
}
