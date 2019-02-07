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

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Eventum\Model\Entity;
use Eventum\Model\Repository\Traits\FindByIdTrait;

class CustomFieldRepository extends EntityRepository
{
    use FindByIdTrait;

    public function getQuery(): QueryBuilder
    {
        return $this->createQueryBuilder('cf');
    }

    public function filterByIssue(QueryBuilder $query, int $issueId): QueryBuilder
    {
        return $query
            ->innerJoin(Entity\IssueCustomField::class, 'icf')
            ->andWhere('cf.id=icf.fieldId')
            ->andWhere('icf.issueId in (:issue_id)')
            ->setParameter('issue_id', $issueId);
    }

    public function filterByProject(QueryBuilder $query, int $projectId): QueryBuilder
    {
        return $query
            ->innerJoin(Entity\ProjectCustomField::class, 'pcf')
            ->andWhere('cf.id=pcf.fieldId')
            ->andWhere('pcf.projectId in (:project_id)')
            ->setParameter('project_id', $projectId);
    }

    /**
     * @param int $prj_id
     * @param int $issue_id
     * @return Entity\CustomField[]
     * @see Custom_Field::getListByIssue
     */
    public function getListByIssue(int $prj_id, int $issue_id): array
    {
        $query = $this->getQuery();
        $this->filterByIssue($query, $issue_id);
        $this->filterByProject($query, $prj_id);

        return $query->getQuery()->getResult();
    }
}
