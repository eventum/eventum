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

use LDAP_Auth_Backend;
use Misc;
use Project;
use Setup;
use User;

class LdapController extends ManageBaseController
{
    /** @var string */
    protected $tpl_name = 'manage/ldap.tpl.html';

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

        $setup['host'] = $post->get('host');
        $setup['port'] = $post->get('port');
        $setup['binddn'] = $post->get('binddn');
        $setup['bindpw'] = $post->get('bindpw');
        $setup['basedn'] = $post->get('basedn');
        $setup['userdn'] = $post->get('userdn');
        $setup['user_filter'] = $post->get('user_filter');
        $setup['customer_id_attribute'] = $post->get('customer_id_attribute');
        $setup['contact_id_attribute'] = $post->get('contact_id_attribute');
        $setup['create_users'] = $post->get('create_users');
        $setup['default_role'] = $post->get('default_role');

        $res = Setup::save(array('ldap' => $setup));

        // FIXME: translations
        $map = array(
            1 => array('Thank you, the setup information was saved successfully.', Misc::MSG_INFO),
            -1 => array("ERROR: The system doesn't have the appropriate permissions " .
                        'to create the configuration file in the setup directory (' . APP_CONFIG_PATH . '). ".
                        "Please contact your local system administrator and ask for write privileges on the provided path.',
                        Misc::MSG_HTML_BOX),
            -2 => array("ERROR: The system doesn't have the appropriate permissions " .
                        'to update the configuration file in the setup directory (' . APP_CONFIG_PATH . '/ldap.php). ".
                        "Please contact your local system administrator ".
                        "and ask for write privileges on the provided filename.',
                        Misc::MSG_HTML_BOX),
        );
        Misc::mapMessages($res, $map);

        $this->tpl->assign('result', $res);
    }

    /**
     * @inheritdoc
     */
    protected function prepareTemplate()
    {
        $setup = Setup::setDefaults('ldap', LDAP_Auth_Backend::getDefaults());

        $this->tpl->assign(
            array(
                'setup' => $setup,
                'project_list' => Project::getAll(),
                'project_roles' => array(0 => 'No Access') + User::getRoles(),
                'user_roles' => User::getRoles(array(User::ROLE_CUSTOMER)),
            )
        );
    }
}
