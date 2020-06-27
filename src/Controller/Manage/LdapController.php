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

        $config = ServiceContainer::getConfig();
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
        $setup['encryption'] = $post->get('encryption');
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

        // clear default_role first, otherwise values will be appended by Laminas\Config
        // https://github.com/eventum/eventum/pull/315#issuecomment-335593325
        $config['ldap']['default_role'] = [];
        $res = Setup::save(['ldap' => $setup]);

        $configPath = Setup::getConfigPath();
        // FIXME: translations
        $map = [
            1 => ['Thank you, the setup information was saved successfully.', MessagesHelper::MSG_INFO],
            -1 => ["ERROR: The system doesn't have the appropriate permissions " .
                        'to create the configuration file in the setup directory (' . $configPath . '). ".
                        "Please contact your local system administrator and ask for write privileges on the provided path.',
                        MessagesHelper::MSG_HTML_BOX, ],
            -2 => ["ERROR: The system doesn't have the appropriate permissions " .
                        'to update the configuration file in the setup directory (' . $configPath . '/ldap.php). ".
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
    protected function prepareTemplate(): void
    {
        $setup = Setup::setDefaults('ldap', $this->getDefaults());

        $this->tpl->assign(
            [
                'setup' => $setup,
                'project_list' => Project::getAll(),
                'project_roles' => [0 => 'No Access'] + User::getRoles(),
                'user_roles' => User::getRoles([User::ROLE_CUSTOMER]),
            ]
        );
    }

    /**
     * Method used to get the system-wide defaults.
     *
     * @return array of the default parameters
     */
    public function getDefaults()
    {
        return [
            'host' => 'localhost',
            'port' => '389',
            'encryption' => 'none',
            'binddn' => '',
            'bindpw' => '',
            'basedn' => 'dc=example,dc=org',
            'user_id_attribute' => '',
            'userdn' => 'uid=%UID%,ou=People,dc=example,dc=org',
            'customer_id_attribute' => '',
            'contact_id_attribute' => '',
            'user_filter' => '',
            'create_users' => null,
            'active_dn' => 'ou=People,dc=example,dc=org',
            'inactive_dn' => 'ou=Inactive Accounts,dc=example,dc=org',
            'default_role' => [],
        ];
    }
}
