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

namespace Eventum\Controller\Manage;

use Auth;
use CRM;
use Custom_Field;
use Eventum\Db\Doctrine;
use Eventum\Extension\ExtensionManager;
use Eventum\Model\Entity\CustomField;
use Eventum\Model\Repository\CustomFieldRepository;
use Eventum\ServiceContainer;
use Project;
use Setup;
use Symfony\Component\HttpFoundation\ParameterBag;
use Throwable;
use User;

class CustomFieldsController extends ManageBaseController
{
    private const ORDER_BY_CHOICES = [
        'cfo_id ASC' => 'Insert',
        'cfo_id DESC' => 'Reverse insert',
        'cfo_value ASC' => 'Alphabetical',
        'cfo_value DESC' => 'Reverse alphabetical',
        'cfo_rank ASC' => 'Manual',
    ];

    /** @var string */
    protected $tpl_name = 'manage/custom_fields.tpl.html';

    /** @var int */
    protected $min_role = User::ROLE_ADMINISTRATOR;

    /** @var string */
    private $cat;

    /** @var CustomFieldRepository */
    private $repo;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $request = $this->getRequest();

        $this->cat = $request->request->get('cat') ?: $request->query->get('cat');
        $this->repo = Doctrine::getCustomFieldRepository();
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction(): void
    {
        switch ($this->cat) {
            case 'new':
                $this->newAction();
                break;
            case 'update':
                $this->updateAction();
                break;
            case 'delete':
                $this->deleteAction();
                break;
            case 'change_rank':
                $this->changeRankAction();
                break;
            case 'edit':
                $id = $this->getRequest()->query->get('id');
                $this->tpl->assign('info', Custom_Field::getDetails($id));
                break;
        }
    }

    private function newAction(): void
    {
        $post = $this->getRequest()->request;

        try {
            $cf = $this->updateFromRequest(new CustomField(), $post);
            $cf->setType($post->get('field_type'));
            $this->repo->persistAndFlush($cf);
            $this->repo->setProjectAssociation($cf, $post->get('projects'));

            $message = ev_gettext('Thank you, the custom field was added successfully.');
            $this->messages->addInfoMessage($message);
            $this->redirect(Setup::getRelativeUrl() . 'manage/custom_fields.php?cat=edit&id=' . $cf->getId());
        } catch (Throwable $e) {
            $this->logger->error($e);
            $message = ev_gettext('An error occurred while trying to add the new custom field.');
            $this->messages->addErrorMessage($message);
        }
    }

    /**
     * @see Custom_Field::updateFieldRelationsFromPost()
     */
    private function updateAction(): void
    {
        $post = $this->getRequest()->request;
        $fld_id = $post->get('id');

        try {
            $cf = $this->updateFromRequest($this->repo->findOrCreate($fld_id), $post);
            $this->repo->setFieldType($cf, $post->get('field_type'));
            $this->repo->persistAndFlush($cf);
            $this->repo->setProjectAssociation($cf, $post->get('projects'));

            $message = ev_gettext('Thank you, the custom field was updated successfully.');
            $this->messages->addInfoMessage($message);
        } catch (Throwable $e) {
            $this->logger->error($e);
            $message = ev_gettext('An error occurred while trying to update the custom field information.');
            $this->messages->addErrorMessage($message);
        }

        $this->redirect(Setup::getRelativeUrl() . 'manage/custom_fields.php?cat=edit&id=' . $fld_id);
    }

    private function deleteAction(): void
    {
        $post = $this->getRequest()->request;
        $fields = $post->get('items', []);

        try {
            foreach ($fields as $fld_id) {
                $cf = $this->repo->findById($fld_id);
                $this->repo->removeCustomField($cf);
            }

            $message = ev_gettext('Thank you, the custom field was removed successfully.');
            $this->messages->addInfoMessage($message);
        } catch (Throwable $e) {
            $this->logger->error($e);
            $message = ev_gettext('An error occurred while trying to remove the custom field information.');
            $this->messages->addErrorMessage($message);
        }

        $this->redirect(Setup::getRelativeUrl() . 'manage/custom_fields.php');
    }

    private function changeRankAction(): void
    {
        $get = $this->getRequest()->query;
        $fld_id = $get->getInt('id');
        $direction = $get->getInt('direction');

        $this->repo->updateRank($fld_id, $direction);
        $this->redirect(Setup::getRelativeUrl() . 'manage/custom_fields.php');
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(): void
    {
        $excluded_roles = [];
        if (!CRM::hasCustomerIntegration(Auth::getCurrentProject())) {
            $excluded_roles[] = User::ROLE_CUSTOMER;
        }
        $user_roles = User::getRoles($excluded_roles);
        $user_roles[User::ROLE_NEVER_DISPLAY] = ev_gettext('Never Display');

        $this->tpl->assign(
            [
                'project_list' => Project::getAll(),
                'list' => $this->getList(),
                'user_roles' => $user_roles,
                'backend_list' => $this->getBackends(),
                'order_by_list' => self::ORDER_BY_CHOICES,
            ]
        );
    }

    /**
     * Method used to get the list of custom fields available in the
     * system.
     */
    private function getList(): array
    {
        $res = [];
        foreach ($this->repo->getList() as $cf) {
            $row = $cf->toArray();
            $row['projects'] = implode(', ', $cf->getProjectTitles());
            $row['min_role_name'] = User::getRole($cf->getMinRole());
            $row['min_role_edit_name'] = User::getRole($cf->getMinRoleEdit());
            $row['has_options'] = $cf->isOptionType();
            $row['field_options'] = $cf->getOptionValues();
            $res[] = $row;
        }

        return $res;
    }

    private function getBackends(): array
    {
        // load classes from extension manager
        /** @var ExtensionManager $manager */
        $manager = ServiceContainer::get(ExtensionManager::class);
        $backends = $manager->getCustomFieldClasses();

        return $this->filterValues($backends);
    }

    /**
     * Create array with key,value from $values $key,
     * i.e discarding values.
     */
    private function filterValues($values): array
    {
        $res = [];
        foreach ($values as $key => $value) {
            $res[$key] = $key;
        }

        return $res;
    }

    private function updateFromRequest(CustomField $cf, ParameterBag $post): CustomField
    {
        return $cf
            ->setTitle($post->get('title'))
            ->setDescription($post->get('description'))
            ->setShowReportForm($post->get('report_form', 0))
            ->setIsReportFormRequired($post->get('report_form_required', 0))
            ->setShowAnonymousForm($post->get('anon_form', 0))
            ->setIsAnonymousFormRequired($post->get('anon_form_required', 0))
            ->setShowListDisplay($post->get('list_display', 0))
            ->setShowCloseForm($post->get('close_form', 0))
            ->setIsCloseFormRequired($post->get('close_form_required', 0))
            ->setIsEditFormRequired($post->get('edit_form_required', 0))
            ->setMinRole($post->get('min_role', User::ROLE_VIEWER))
            ->setMinRoleEdit($post->get('min_role_edit', User::ROLE_VIEWER))
            ->setRank($post->getInt('rank') ?: $this->repo->getNextRank())
            ->setOrderBy($post->get('order_by', 'cfo_id ASC'))
            ->setBackendClass($post->get('custom_field_backend'));
    }
}
