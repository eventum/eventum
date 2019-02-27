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

use Custom_Field;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Eventum\Model\Entity;
use Eventum\Model\Repository\Traits\FindByIdTrait;
use RuntimeException;

/**
 * @method Entity\CustomField findById(int $fld_id)
 */
class CustomFieldRepository extends EntityRepository
{
    use FindByIdTrait;

    public function persistAndFlush(Entity\CustomField $cf): void
    {
        $em = $this->getEntityManager();
        $em->persist($cf);
        $em->flush($cf);
    }

    public function findOrCreate(int $id): Entity\CustomField
    {
        $cf = $this->find($id);
        if (!$cf) {
            $cf = new Entity\CustomField();
        }

        return $cf;
    }

    private function getQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('cf');
    }

    private function filterIssue(QueryBuilder $qb, int $issueId): self
    {
        $qb
            ->leftJoin(Entity\IssueCustomField::class, 'icf',
                Join::WITH,
                'cf.id=icf.fieldId AND icf.issueId=:issue_id'
            )
            ->setParameter('issue_id', $issueId);

        return $this;
    }

    private function filterProject(QueryBuilder $qb, int $projectId): self
    {
        $qb
            ->innerJoin(Entity\ProjectCustomField::class, 'pcf')
            ->andWhere('cf.id=pcf.fieldId')
            ->andWhere('pcf.projectId=:project_id')
            ->setParameter('project_id', $projectId);

        return $this;
    }

    private function filterRole(QueryBuilder $qb, int $minRole, bool $forEdit = false): self
    {
        $qb
            ->andWhere('cf.minRole <= :min_role')
            ->setParameter('min_role', $minRole);

        if ($forEdit) {
            $qb
                ->andWhere('cf.minRoleEdit <= :min_role_edit')
                ->setParameter('min_role_edit', $minRole);
        }

        return $this;
    }

    private function filterFormType(QueryBuilder $qb, ?string $formType): self
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
            $qb->andWhere("cf.{$fieldName}=1");
        }

        return $this;
    }

    private function filterFieldType(QueryBuilder $qb, ?string $fieldType): self
    {
        if (!$fieldType) {
            return $this;
        }

        $qb
            ->andWhere('cf.type = :field_type')
            ->setParameter('field_type', $fieldType);

        return $this;
    }

    /**
     * @return Entity\CustomField[]
     * @see Custom_Field::getListByIssue
     */
    public function getListByIssue(int $prj_id, int $issue_id, int $minRole, ?string $formType, bool $forEdit): array
    {
        $qb = $this->getQueryBuilder();

        $this
            ->filterProject($qb, $prj_id)
            ->filterIssue($qb, $issue_id)
            ->filterFormType($qb, $formType)
            ->filterRole($qb, $minRole, $forEdit);

        $qb->addOrderBy('cf.rank');

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Entity\CustomField[]
     * @see Custom_Field::getListByProject
     */
    public function getListByProject(int $prj_id, int $minRole, ?string $formType, ?string $fieldType = null, $forEdit = false): array
    {
        $qb = $this->getQueryBuilder();

        $this
            ->filterProject($qb, $prj_id)
            ->filterFormType($qb, $formType)
            ->filterFieldType($qb, $fieldType)
            ->filterRole($qb, $minRole, $forEdit);

        $qb->addOrderBy('cf.rank');

        return $qb->getQuery()->getResult();
    }

    public function updateCustomFieldOptions(int $fld_id, array $updateOptions, array $addOptions): void
    {
        $cf = $this->findById($fld_id);
        $options = $cf->getOptions();
        $em = $this->getEntityManager();

        $rank = 1;
        foreach ($updateOptions as $cfo_id => $cfo_value) {
            $cfo = $cf->updateOptionValue($cfo_id, $cfo_value, $rank++);
            $em->persist($cfo);
            $options->removeElement($cfo);
        }

        foreach ($options as $cfo) {
            $em->remove($cfo);
        }

        foreach ($addOptions as $cfo_value) {
            if (!$cfo_value) {
                continue;
            }

            $cfo = $cf->addOptionValue($cfo_value, $rank++);
            $em->persist($cfo);
        }

        $em->flush();
    }

    public function removeCustomField(Entity\CustomField $cf): void
    {
        // TODO: use query builder, as this will not perform for large database
        $em = $this->getEntityManager();

        // TODO: these foreach would not be needed if delete cascade is enabled
        foreach ($cf->getOptions() as $cfo) {
            $em->remove($cfo);
        }
        foreach ($cf->getIssues() as $icf) {
            $em->remove($icf);
        }
        foreach ($cf->getProjects() as $pcf) {
            $em->remove($pcf);
        }

        $em->remove($cf);
        $em->flush();
    }

    public function setProjectAssociation(Entity\CustomField $cf, array $projects): void
    {
        $em = $this->getEntityManager();
        $collection = $cf->getProjects();

        foreach ($projects as $prj_id) {
            $pcf = $cf->addProjectById($prj_id);
            $em->persist($pcf);
            $collection->removeElement($pcf);
        }

        foreach ($collection as $pcf) {
            $em->remove($pcf);
        }

        $em->flush();
    }

    /**
     * Set field type.
     * May need to remove all custom field options if the field is being changed from a combo box to a text field
     */
    public function setFieldType(Entity\CustomField $cf, string $fieldType): void
    {
        if ($cf->getType() === $fieldType) {
            return;
        }

        $em = $this->getEntityManager();
        $fld_id = $cf->getId();
        $wasTextField = $cf->isTextType();

        $cf->setType($fieldType);
        $isOptionType = $cf->isOptionType();

        if (!$wasTextField && !$isOptionType) {
            $cfoIds = [];
            foreach ($cf->getOptions() as $cfo) {
                $cfoIds[] = $cfo->getId();
                $em->remove($cfo);
            }

            // also remove any custom field option that is currently assigned to an issue
            foreach ($cf->getIssues() as $icf) {
                if (in_array($icf->getStringValue(), $cfoIds, true)) {
                    $em->remove($icf);
                }
            }
        }

        // update values for all other option types
        if ($cf->isOtherType()) {
            foreach ($cf->getIssues() as $icf) {
                $icf->updateValuesForNewType();
                $em->persist($icf);
            }
        }

        $em->flush();
    }
}
