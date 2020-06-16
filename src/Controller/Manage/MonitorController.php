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

use Eventum\Controller\Helper\MessagesHelper;
use Eventum\ServiceContainer;
use Project;
use Setup;
use User;

class MonitorController extends ManageBaseController
{
    /** @var string */
    protected $tpl_name = 'manage/monitor.tpl.html';

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
        if ($this->cat == 'update') {
            $this->updateAction();
        }
    }

    private function updateAction(): void
    {
        $post = $this->getRequest()->request;

        $setup = [
            'diskcheck' => $post->get('diskcheck'),
            'paths' => $post->get('paths'),
            'ircbot' => $post->get('ircbot'),
        ];
        $res = Setup::save(['monitor' => $setup]);

        $setupFile = Setup::getSetupFile();
        $configPath = Setup::getConfigPath();
        $map = [
            1 => [ev_gettext('Thank you, the setup information was saved successfully.'), MessagesHelper::MSG_INFO],
            -1 => [ev_gettext(
                "ERROR: The system doesn't have the appropriate permissions " .
                'to create the configuration file in the setup directory (%s). ' .
                'Please contact your local system administrator ' .
                'and ask for write privileges on the provided path.',
                $configPath
            ), MessagesHelper::MSG_NOTE_BOX],
            -2 => [ev_gettext(
                "ERROR: The system doesn't have the appropriate permissions " .
                'to update the configuration file in the setup directory (%s). ' .
                'Please contact your local system administrator and ask ' .
                'for write privileges on the provided filename.',
                $setupFile
            ), MessagesHelper::MSG_NOTE_BOX],
        ];
        $this->messages->mapMessages($res, $map);
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(): void
    {
        $this->tpl->assign(
            [
                'project_list' => Project::getAll(),
                'enable_disable' => [
                    'enabled' => ev_gettext('Enabled'),
                    'disabled' => ev_gettext('Disabled'),
                ],
                'setup' => ServiceContainer::getConfig(),
            ]
        );
    }
}
