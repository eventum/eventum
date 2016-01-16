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

use Misc;
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
     * @inheritdoc
     */
    protected function configure()
    {
        $request = $this->getRequest();

        $this->cat = $request->request->get('cat') ?: $request->query->get('cat');
    }

    /**
     * @inheritdoc
     */
    protected function defaultAction()
    {
        if ($this->cat == 'update') {
            $this->updateAction();
        }
    }

    private function updateAction()
    {
        $post = $this->getRequest()->request;

        $setup = array(
            'diskcheck' => $post->get('diskcheck'),
            'paths' => $post->get('paths'),
            'ircbot' => $post->get('ircbot'),
        );
        $res = Setup::save(array('monitor' => $setup));

        $map = array(
            1 => array(ev_gettext('Thank you, the setup information was saved successfully.'), Misc::MSG_INFO),
            -1 => array(ev_gettext(
                            "ERROR: The system doesn't have the appropriate permissions " .
                            'to create the configuration file in the setup directory (%s). ' .
                            'Please contact your local system administrator ' .
                            'and ask for write privileges on the provided path.',
                            APP_CONFIG_PATH
                        ),
                        Misc::MSG_NOTE_BOX),
            -2 => array(ev_gettext(
                            "ERROR: The system doesn't have the appropriate permissions " .
                            'to update the configuration file in the setup directory (%s). ' .
                            'Please contact your local system administrator and ask ' .
                            'for write privileges on the provided filename.',
                            APP_SETUP_FILE
                        ),
                        Misc::MSG_NOTE_BOX),
        );
        Misc::mapMessages($res, $map);
    }

    /**
     * @inheritdoc
     */
    protected function prepareTemplate()
    {
        $this->tpl->assign(
            array(
                'project_list' => Project::getAll(),
                'enable_disable' => array(
                    'enabled' => ev_gettext('Enabled'),
                    'disabled' => ev_gettext('Disabled'),
                ),
                'setup' => Setup::get(),
            )
        );
    }
}
