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
use Setup;
use User;

class ScmController extends ManageBaseController
{
    /** @var string */
    protected $tpl_name = 'manage/scm.tpl.html';

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
        if ($this->cat === 'update') {
            $this->updateAction();
        }
    }

    private function updateAction(): void
    {
        $post = $this->getRequest()->request;

        $setup = ['scm_integration' => $post->get('scm_integration')];
        $res = Setup::save($setup);
        $this->tpl->assign('result', $res);

        $setupFile = Setup::getSetupFile();
        $configPath = Setup::getConfigPath();

        $map = [
            1 => [ev_gettext('Thank you, the setup information was saved successfully.'), MessagesHelper::MSG_INFO],
            -1 => [ev_gettext(
                "ERROR: The system doesn't have the appropriate permissions to create the configuration file in the setup directory (%1\$s). " .
                'Please contact your local system administrator and ask for write privileges on the provided path.',
                $configPath
            ), MessagesHelper::MSG_NOTE_BOX],
            -2 => [ev_gettext(
                "ERROR: The system doesn't have the appropriate permissions to update the configuration file in the setup directory (%1\$s). " .
                'Please contact your local system administrator and ask for write privileges on the provided filename.',
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
                'scm_ping_url' => Setup::getBaseUrl() . 'scm_ping.php',
                'setup' => ServiceContainer::getConfig(),
            ]
        );
    }
}
