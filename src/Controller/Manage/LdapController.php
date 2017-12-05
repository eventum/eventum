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
use LDAP_Auth_Backend;
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
     * {@inheritdoc}
     */
    protected function configure()
    {
        $request = $this->getRequest();

        $this->cat = $request->request->get('cat') ?: $request->query->get('cat');
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction()
    {
        if ($this->cat === 'update') {
            $this->updateAction();
        }
    }

    private function updateAction()
    {
        $post = $this->getRequest()->request;

        $config = Setup::get();
        $setup = $config['ldap']->toArray();

        // special handling for binddn/bindpw:
        // update bindpw only if submitted new value
        // but as this makes impossible to clear its value
        // clear bindpw if binddn is empty
        $setup['binddn'] = $post->get('binddn');
        if ($post->get('bindpw')) {
            $setup['bindpw'] = $post->get('bindpw');
        }
        if ($setup['binddn'] === '') {
            $setup['bindpw'] = '';
        }

        $setup['host'] = $post->get('host');
        $setup['port'] = $post->get('port');
        $setup['basedn'] = $post->get('basedn');
        $setup['user_id_attribute'] = $post->get('user_id_attribute');
        $setup['userdn'] = $post->get('userdn');
        $setup['user_filter'] = $post->get('user_filter');
        $setup['customer_id_attribute'] = $post->get('customer_id_attribute');
        $setup['contact_id_attribute'] = $post->get('contact_id_attribute');
        $setup['active_dn'] = $post->get('active_dn');
        $setup['inactive_dn'] = $post->get('inactive_dn');
        $setup['create_users'] = $post->get('create_users');
        $setup['default_role'] = $post->get('default_role');

        // clear default_role first, otherwise values will be appended by Zend\Config
        // https://github.com/eventum/eventum/pull/315#issuecomment-335593325
        $config['ldap']['default_role'] = [];
        $res = Setup::save(['ldap' => $setup]);

        // FIXME: translations
        $map = [
            1 => ['Thank you, the setup information was saved successfully.', MessagesHelper::MSG_INFO],
            -1 => ["ERROR: The system doesn't have the appropriate permissions " .
                        'to create the configuration file in the setup directory (' . APP_CONFIG_PATH . '). ".
                        "Please contact your local system administrator and ask for write privileges on the provided path.',
                        MessagesHelper::MSG_HTML_BOX, ],
            -2 => ["ERROR: The system doesn't have the appropriate permissions " .
                        'to update the configuration file in the setup directory (' . APP_CONFIG_PATH . '/ldap.php). ".
                        "Please contact your local system administrator ".
                        "and ask for write privileges on the provided filename.',
                   MessagesHelper::MSG_HTML_BOX, ],
        ];
        $this->messages->mapMessages($res, $map);

        $this->redirect('ldap.php');
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate()
    {
        $setup = Setup::setDefaults('ldap', LDAP_Auth_Backend::getDefaults());

        $this->tpl->assign(
            [
                'setup' => $setup,
                'project_list' => Project::getAll(),
                'project_roles' => [0 => 'No Access'] + User::getRoles(),
                'user_roles' => User::getRoles([User::ROLE_CUSTOMER]),
            ]
        );
    }
}
