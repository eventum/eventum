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
use Eventum\Controller\Helper\MessagesHelper;
use Eventum\Db\Doctrine;
use Eventum\Extension\ExtensionManager;
use Eventum\Model\Entity\CustomField;
use Project;
use Symfony\Component\HttpFoundation\ParameterBag;
use Throwable;
use User;

class CustomFieldsController extends ManageBaseController
{
    /** @var string */
    protected $tpl_name = 'manage/custom_fields.tpl.html';

    /** @var int */
    protected $min_role = User::ROLE_ADMINISTRATOR;

    /** @var string */
    private $cat;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $request = $this->getRequest();

        $this->cat = $request->request->get('cat') ?: $request->query->get('cat');
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
        $res = Custom_Field::insert();
        $map = [
            1 => [ev_gettext('Thank you, the custom field was added successfully.'), MessagesHelper::MSG_INFO],
            -1 => [ev_gettext('An error occurred while trying to add the new custom field.'), MessagesHelper::MSG_ERROR],
        ];
        $this->messages->mapMessages($res, $map);
    }

    /**
     * @see Custom_Field::updateFieldRelationsFromPost()
     */
    private function updateAction(): void
    {
        $post = $this->getRequest()->request;
        $fld_id = $post->get('id');

        try {
            $repo = Doctrine::getCustomFieldRepository();
            $cf = $this->updateFromRequest($repo->findOrCreate($fld_id), $post);
            $repo->setFieldType($cf, $post->get('field_type'));
            $repo->persistAndFlush($cf);
            $repo->setProjectAssociation($cf, $post->get('projects'));

            $message = ev_gettext('Thank you, the custom field was updated successfully.');
            $this->messages->addInfoMessage($message);
        } catch (Throwable $e) {
            $this->logger->error($e);
            $message = ev_gettext('An error occurred while trying to update the custom field information.');
            $this->messages->addErrorMessage($message);
        }

        $this->redirect(APP_RELATIVE_URL . 'manage/custom_fields.php?cat=edit&id=' . $fld_id);
    }

    private function deleteAction(): void
    {
        $post = $this->getRequest()->request;
        $fields = $post->get('items', []);

        try {
            $repo = Doctrine::getCustomFieldRepository();

            foreach ($fields as $fld_id) {
                $cf = $repo->findById($fld_id);
                $repo->removeCustomField($cf);
            }

            $message = ev_gettext('Thank you, the custom field was removed successfully.');
            $this->messages->addInfoMessage($message);
        } catch (Throwable $e) {
            $this->logger->error($e);
            $message = ev_gettext('An error occurred while trying to remove the custom field information.');
            $this->messages->addErrorMessage($message);
        }

        $this->redirect(APP_RELATIVE_URL . 'manage/custom_fields.php');
    }

    private function changeRankAction(): void
    {
        Custom_Field::changeRank();
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
                'list' => Custom_Field::getList(),
                'user_roles' => $user_roles,
                'backend_list' => $this->getBackends(),
                'order_by_list' => Custom_Field::$order_by_choices,
            ]
        );
    }

    private function getBackends()
    {
        // load classes from extension manager
        $manager = ExtensionManager::getManager();
        $backends = $manager->getCustomFieldClasses();

        return $this->filterValues($backends);
    }

    /**
     * Create array with key,value from $values $key,
     * i.e discarding values.
     */
    private function filterValues($values)
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
            ->setRank($post->get('rank', Custom_Field::getMaxRank() + 1))
            ->setOrderBy($post->get('order_by', 'cfo_id ASC'))
            ->setBackend($post->get('custom_field_backend'));
    }
}
