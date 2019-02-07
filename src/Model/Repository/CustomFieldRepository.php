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
use RuntimeException;

class CustomFieldRepository extends EntityRepository
{
    use FindByIdTrait;

    public function getQuery(): QueryBuilder
    {
        return $this->createQueryBuilder('cf');
    }

    public function filterIssue(QueryBuilder $query, int $issueId): self
    {
        $query
            ->innerJoin(Entity\IssueCustomField::class, 'icf')
            ->andWhere('cf.id=icf.fieldId')
            ->andWhere('icf.issueId=:issue_id')
            ->setParameter('issue_id', $issueId);

        return $this;
    }

    public function filterProject(QueryBuilder $query, int $projectId): self
    {
        $query
            ->innerJoin(Entity\ProjectCustomField::class, 'pcf')
            ->andWhere('cf.id=pcf.fieldId')
            ->andWhere('pcf.projectId=:project_id')
            ->setParameter('project_id', $projectId);

        return $this;
    }

    public function filterRole(QueryBuilder $query, int $minRole, bool $forEdit = false): self
    {
        $query
            ->andWhere('cf.minRole <= :min_role')
            ->setParameter('min_role', $minRole);

        if ($forEdit) {
            $query
                ->andWhere('cf.minRoleEdit <= :min_role_edit')
                ->setParameter('min_role_edit', $minRole);
        }

        return $this;
    }

    public function filterFormType(QueryBuilder $query, ?string $formType): self
    {
        if ($formType === null) {
            return $this;
        }

        $formTypes = Entity\CustomField::FORM_TYPES;
        if (!array_key_exists($formType, $formTypes)) {
            throw new RuntimeException("Unsupported form type: '$formType'");
        }

        $fieldName = $formTypes[$formType];
        if ($fieldName) {
            $query->andWhere("cf.{$fieldName}=1");
        }

        return $this;
    }

    /**
     * @return Entity\CustomField[]
     * @see Custom_Field::getListByIssue
     */
    public function getListByIssue(int $prj_id, int $issue_id, int $minRole, ?string $formType, bool $forEdit): array
    {
        $query = $this->getQuery();

        $this
            ->filterIssue($query, $issue_id)
            ->filterProject($query, $prj_id)
            ->filterRole($query, $minRole, $forEdit)
            ->filterFormType($query, $formType);

        $query->addOrderBy('cf.rank');

        // return from issue custom field point
        $query->select('icf');

        return $query->getQuery()->getResult();
    }
}
