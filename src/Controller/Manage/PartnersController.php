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

use Eventum\Db\DatabaseException;
use Eventum\Db\Doctrine;
use Eventum\Extension\ExtensionManager;
use Eventum\ServiceContainer;
use Partner;
use Project;

class PartnersController extends ManageBaseController
{
    /** @var string */
    protected $tpl_name = 'manage/partners.tpl.html';

    /** @var string */
    private $cat;

    protected function configure(): void
    {
        $request = $this->getRequest();

        $this->cat = $request->request->get('cat') ?: $request->query->get('cat');
    }

    protected function defaultAction(): void
    {
        if ($this->cat === 'update') {
            $this->updateAction();
        } elseif ($this->cat === 'edit') {
            $this->editAction();
        }
    }

    private function updateAction(): void
    {
        $request = $this->getRequest()->request;

        $code = $request->get('code');
        try {
            $repo = Doctrine::getPartnerProjectRepository();
            $pap = $repo->findOneByCode($code);
            $repo->setProjectAssociation($pap, $request->get('projects'));
        } catch (DatabaseException $e) {
            $this->messages->addErrorMessage(ev_gettext('An error occurred while trying to update the partner information.'));

            return;
        }

        $this->messages->addInfoMessage(ev_gettext('Thank you, the partner was updated successfully.'));
        $this->redirect("partners.php?cat=edit&code={$code}");
    }

    private function editAction(): void
    {
        $request = $this->getRequest()->query;

        $info = $this->getDetails($request->get('code'));
        $this->tpl->assign('info', $info);
    }

    protected function prepareTemplate(): void
    {
        $this->tpl->assign(
            [
                'type' => 'partners',
                'list' => $this->getPartnersList(),
                'project_list' => Project::getAll(),
            ]
        );
    }

    private function getProjects(string $par_code): array
    {
        $repo = Doctrine::getProjectRepository();
        $res = [];

        foreach ($repo->findByPartnerCode($par_code) as $project) {
            $res[$project->getId()] = $project->getTitle();
        }

        return $res;
    }

    private function getDetails(string $par_code): array
    {
        return [
            'code' => $par_code,
            'name' => Partner::getBackend($par_code)->getName(),
            'projects' => $this->getProjects($par_code),
        ];
    }

    /**
     * Return list of available Partner backends.
     *
     * @return array
     */
    private function getPartnersList(): array
    {
        $partners = [];
        /** @var ExtensionManager $em */
        $em = ServiceContainer::get(ExtensionManager::class);
        $backends = $em->getPartnerClasses();
        foreach ($backends as $par_code => $backend) {
            $partners[] = [
                'code' => $par_code,
                'name' => $backend->getName(),
                'projects' => $this->getProjects($par_code),
            ];
        }

        return $partners;
    }
}
