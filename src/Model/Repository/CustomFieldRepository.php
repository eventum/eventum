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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Eventum\Model\Entity;
use History;
use RuntimeException;

/**
 * @method Entity\CustomField findById(int $fld_id)
 */
class CustomFieldRepository extends EntityRepository
{
    use Traits\FindByIdTrait;

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
            ->leftJoin(
                Entity\IssueCustomField::class,
                'icf',
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
     * Returns next maximum rank of any custom fields.
     */
    public function getNextRank(): int
    {
        $qb = $this->getQueryBuilder();

        $qb->select('MAX(cf.rank) AS rank');

        $rank = $qb->getQuery()->getSingleScalarResult();

        return $rank + 1;
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

    /**
     * @return Entity\CustomField[]|ArrayCollection
     */
    public function getList(): ArrayCollection
    {
        $qb = $this->getQueryBuilder();

        $qb->addOrderBy('cf.rank');

        return new ArrayCollection($qb->getQuery()->getResult());
    }

    public function updateCustomFieldValues(int $issue_id, int $role_id, array $custom_fields): array
    {
        $em = $this->getEntityManager();
        $updated_fields = [];

        foreach ($custom_fields as $fld_id => $value) {
            $cf = $this->findById($fld_id);

            // security check
            if ($cf->getMinRole() > $role_id) {
                continue;
            }

            $field = [
                'title' => $cf->getTitle(),
                'type' => $cf->getType(),
                'min_role' => $cf->getMinRole(),
                'changes' => '',
                'old_display' => '',
                'new_display' => '',
            ];
            $currentValues = $cf->getIssueOptionValues($issue_id);

            if ($cf->isOptionType()) {
                // remove dummy value from checkboxes. This dummy value is required so all real values can be unchecked.
                if ($cf->getType() === 'checkbox') {
                    $value = array_filter($value);
                }

                $old_value = $currentValues->toArray();
                if (!is_array($value)) {
                    $value = [$value];
                }

                $hasChanges = count(array_diff($old_value, $value)) > 0 || count(array_diff($value, $old_value)) > 0;
                if (!$hasChanges) {
                    continue;
                }

                $old_display_value = $cf->getDisplayValue($issue_id);
                $this->setIssueAssociation($cf, $issue_id, $value);
                $new_display_value = $cf->getDisplayValue($issue_id);

                $field['changes'] = History::formatChanges($old_display_value, $new_display_value);
                $field['old_display'] = $old_display_value;
                $field['new_display'] = $new_display_value;
            } else {
                $old_value = $currentValues->first() ?: '';
                if ($old_value === $value) {
                    continue;
                }

                $icf = $cf->getIssueCustomField($issue_id);
                if (!$icf) {
                    $icf = $cf->addIssueCustomField($issue_id, $value);
                } else {
                    $icf->setValue($value);
                }
                $em->persist($icf);

                $field['old_display'] = $old_value;
                $field['new_display'] = $value;

                if ($cf->getType() === 'textarea') {
                    $field['changes'] = '';
                } else {
                    $field['changes'] = History::formatChanges($old_value, $value);
                }
            }

            $updated_fields[$fld_id] = $field;
        }

        $em->flush();

        return $updated_fields;
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

    public function updateRank(int $fld_id, int $direction): void
    {
        $fields = $this->getList();

        $cf = $fields->filter(static function (Entity\CustomField $cf) use ($fld_id) {
            return $cf->getId() === $fld_id;
        })->first();

        if (!$cf) {
            return;
        }

        // trying to move first entry lower or last entry higher will not work
        if ($direction === -1 && $cf === $fields->first()) {
            return;
        }
        if ($direction === +1 && $cf === $fields->last()) {
            return;
        }

        $index = $fields->indexOf($cf);

        // swap the fields
        $fields[$index] = $fields[$index + $direction];
        $fields[$index + $direction] = $cf;

        // re-order everything starting from 1
        $em = $this->getEntityManager();
        $rank = 1;
        foreach ($fields as $cf) {
            $cf->setRank($rank++);
            $em->persist($cf);
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
        foreach ($cf->getProjectCustomFields() as $pcf) {
            $em->remove($pcf);
        }

        $em->remove($cf);
        $em->flush();
    }

    public function setProjectAssociation(Entity\CustomField $cf, array $projects): void
    {
        $em = $this->getEntityManager();
        $projectRepo = $em->getRepository(Entity\Project::class);
        $collection = clone $cf->getProjectCustomFields();

        foreach ($projects as $prj_id) {
            $pcf = $cf->getProjectCustomFieldById($prj_id);
            if (!$pcf) {
                $project = $projectRepo->findById($prj_id);
                $pcf = new Entity\ProjectCustomField();
                $pcf->setProject($project);
                $cf->addProjectCustomField($pcf);
            }

            $em->persist($pcf);
            $collection->removeElement($pcf);
        }

        foreach ($collection as $pcf) {
            $em->remove($pcf);
        }

        $em->flush();
    }

    private function setIssueAssociation(Entity\CustomField $cf, int $issue_id, array $values): void
    {
        $em = $this->getEntityManager();
        $collection = $cf->getIssueCustomFields($issue_id);

        $isOptionType = $cf->isOptionType();
        foreach ($values as $value) {
            // "-1" means: don't store placeholder values to database
            // https://github.com/eventum/eventum/blob/v3.6.1/lib/eventum/class.custom_field.php#L448
            // https://github.com/eventum/eventum/commit/b2d0e72800c10ba6b4faa6c6d7e4460ebe60e46d
            if ($isOptionType && $value === '-1') {
                continue;
            }
            $icf = $cf->updateIssueCustomField($issue_id, $value);
            $em->persist($icf);
            $collection->removeElement($icf);
        }

        foreach ($collection as $icf) {
            $em->remove($icf);
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
