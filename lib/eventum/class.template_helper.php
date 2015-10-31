<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2015 Eventum Team.                              |
// |                                                                      |
// | This program is free software; you can redistribute it and/or modify |
// | it under the terms of the GNU General Public License as published by |
// | the Free Software Foundation; either version 2 of the License, or    |
// | (at your option) any later version.                                  |
// |                                                                      |
// | This program is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
// | GNU General Public License for more details.                         |
// |                                                                      |
// | You should have received a copy of the GNU General Public License    |
// | along with this program; if not, write to:                           |
// |                                                                      |
// | Free Software Foundation, Inc.                                       |
// | 51 Franklin Street, Suite 330                                        |
// | Boston, MA 02110-1301, USA.                                          |
// +----------------------------------------------------------------------+

/**
 * Class used to abstract the backend template system used by the site. This
 * is especially useful to be able to change template backends in the future
 * without having to rewrite all PHP based scripts.
 */
class Template_Helper
{
    /** @var Smarty */
    private $smarty;

    /** @var string */
    private $tpl_name;

    /**
     * Constructor of the class
     */
    public function __construct($tpl_name = null)
    {
        $smarty = new Smarty();
        // TODO: remove "APP_LOCAL_PATH" from the list in 2.4.1
        $smarty->setTemplateDir(array(APP_LOCAL_PATH . '/templates', APP_LOCAL_PATH, APP_TPL_PATH));
        $smarty->setCompileDir(APP_TPL_COMPILE_PATH);

        $smarty->addPluginsDir(array(APP_INC_PATH . '/smarty'));

        $smarty->registerPlugin('modifier', 'activateLinks', array('Link_Filter', 'activateLinks'));
        $smarty->registerPlugin('modifier', 'activateAttachmentLinks', array('Link_Filter', 'activateAttachmentLinks'));
        $smarty->registerPlugin('modifier', 'formatCustomValue', array('Custom_Field', 'formatValue'));
        $smarty->registerPlugin('modifier', 'bool', array('Misc', 'getBooleanDisplayValue'));
        $smarty->registerPlugin('modifier', 'format_date', array('Date_Helper', 'getFormattedDate'));

        // Fixes problem with CRM API and dynamic includes.
        // See https://code.google.com/p/smarty-php/source/browse/trunk/distribution/3.1.16_RELEASE_NOTES.txt?spec=svn4800&r=4800
        if (isset($smarty->inheritance_merge_compiled_includes)) {
            $smarty->inheritance_merge_compiled_includes = false;
        }

        // this avoids loading it twice when using composer
        if (function_exists('smarty_block_t')) {
            $smarty->registerPlugin('block', 't', 'smarty_block_t');
        }

        if ($tpl_name) {
            $this->setTemplate($tpl_name);
        }

        $this->smarty = $smarty;
    }

    /**
     * Sets the internal template filename for the current PHP script
     *
     * @param  string $tpl_name The filename of the template
     * @return $this
     */
    public function setTemplate($tpl_name)
    {
        $this->tpl_name = $tpl_name;

        return $this;
    }

    /**
     * Assigns variables to specific placeholders on the target template
     *
     * @param  string $var_name Placeholder on the template
     * @param  string $value Value to be assigned to this placeholder
     * @return $this
     */
    public function assign($var_name, $value = '')
    {
        if (!is_array($var_name)) {
            $this->smarty->assign($var_name, $value);
        } else {
            $this->smarty->assign($var_name);
        }

        return $this;
    }

    /**
     * Prints the actual parsed template.
     *
     * @param bool $process Whether to call process template to fill template variables. Default true
     * @return $this
     */
    public function displayTemplate($process = true)
    {
        if ($process) {
            $this->processTemplate();
        }

        // finally display the parsed template
        $this->smarty->display($this->tpl_name);

        return $this;
    }

    /**
     * Returns the contents of the parsed template
     *
     * @param bool $process Whether to call process template to fill template variables. Default true
     * @return string The contents of the parsed template
     */
    public function getTemplateContents($process = true)
    {
        if ($process) {
            $this->processTemplate();
        }

        return $this->smarty->fetch($this->tpl_name);
    }

    private static function getVcsVersion()
    {
        // Try APP_VERSION match:
        // "Eventum 2.3.3-148-g78b3368"
        // "Eventum 2.4.0-pre1-285-g298325e"
        if (preg_match('/^[\d.]+(?:-[^-]+)(?:-\d+)?-g(?P<hash>[0-9a-f]+)$/', APP_VERSION, $m)) {
            return $m['hash'];
        }

        // if version ends with "-dev", try look into VCS
        if (substr(APP_VERSION, -4) == '-dev' && file_exists($file = APP_PATH . '/.git/HEAD')) {
            list(, $refname) = explode(': ', file_get_contents($file));
            if (!file_exists($file = APP_PATH . '/.git/' . trim($refname))) {
                return null;
            }
            $hash = file_get_contents($file);

            return substr($hash, 0, 7);
        }

        // probably release version
        return null;
    }

    /**
     * Processes the template and assign common variables automatically.
     *
     * @return $this
     */
    private function processTemplate()
    {
        $core = array(
            'rel_url' => APP_RELATIVE_URL,
            'base_url' => APP_BASE_URL,
            'app_title' => APP_NAME,
            'app_version' => APP_VERSION,
            'app_setup' => Setup::get(),
            'messages' => Misc::getMessages(),
            'roles' => User::getAssocRoleIDs(),
            'auth_backend' => APP_AUTH_BACKEND,
            'current_url' => $_SERVER['PHP_SELF'],
        );

        // If VCS version is present "Eventum 2.3.3-148-g78b3368", link ref to github
        $vcsVersion = self::getVcsVersion();
        if ($vcsVersion) {
            $link = "https://github.com/eventum/eventum/commit/{$vcsVersion}";
            $core['application_version_link'] = $link;
            // append VCS version if not yet there
            if (!preg_match('/-g[0-9a-f]+$/', APP_VERSION)) {
                $core['app_version'] = "v{$core['app_version']}-g{$vcsVersion}";
            }
        }

        $usr_id = Auth::getUserID();
        if ($usr_id) {
            $core['user'] = User::getDetails($usr_id);
            $prj_id = Auth::getCurrentProject();
            $setup = Setup::get();
            if (!empty($prj_id)) {
                $role_id = User::getRoleByUser($usr_id, $prj_id);
                $has_crm = CRM::hasCustomerIntegration($prj_id);
                $core = $core + array(
                        'project_id' => $prj_id,
                        'project_name' => Auth::getCurrentProjectName(),
                        'has_crm' => $has_crm,
                        'current_role' => $role_id,
                        'current_role_name' => User::getRole($role_id),
                        'feature_access' => Access::getFeatureAccessArray($usr_id),
                    );
                if ($has_crm) {
                    $crm = CRM::getInstance($prj_id);
                    $core['crm_template_path'] = $crm->getTemplatePath();
                    if ($role_id == User::ROLE_CUSTOMER) {
                        try {
                            $contact = $crm->getContact($core['user']['usr_customer_contact_id']);
                            $core['allowed_customers'] = $contact->getCustomers();
                            $core['current_customer'] = $crm->getCustomer(Auth::getCurrentCustomerID(false));
                        } catch (CRMException $e) {
                        }
                    }
                }
            }
            $info = User::getDetails($usr_id);
            $raw_projects = Project::getAssocList(Auth::getUserID(), false, true);
            $active_projects = array();
            foreach ($raw_projects as $prj_id => $prj_info) {
                if ($prj_info['status'] == 'archived') {
                    $prj_info['prj_title'] .= ' ' . ev_gettext('(archived)');
                }
                $active_projects[$prj_id] = $prj_info['prj_title'];
            }
            $core = $core + array(
                    'active_projects' => $active_projects,
                    'current_full_name' => $info['usr_full_name'],
                    'current_email' => $info['usr_email'],
                    'current_user_id' => $usr_id,
                    'current_user_datetime' => Date_Helper::getISO8601date('now', '', true),
                    'is_current_user_clocked_in' => User::isCLockedIn($usr_id),
                    'is_anon_user' => Auth::isAnonUser(),
                    'is_current_user_partner' => !empty($info['usr_par_code']),
                    'roles' => User::getAssocRoleIDs(),
                    'current_user_prefs' => Prefs::get(Auth::getUserID()),

                );
            $this->assign('current_full_name', $core['user']['usr_full_name']);
            $this->assign('current_email', $core['user']['usr_email']);
            $this->assign('current_user_id', $usr_id);
            $this->assign('handle_clock_in', $setup['handle_clock_in'] == 'enabled');
            $this->assign('is_current_user_clocked_in', User::isClockedIn($usr_id));
            $this->assign('roles', User::getAssocRoleIDs());
        }
        $this->assign('core', $core);

        $this->addDebugbar(isset($role_id) ? $role_id : null);

        return $this;
    }

    /**
     * Setup Debug Bar:
     * - if initialized
     * - if role_id is set
     * - if user is administrator
     *
     * @throws \DebugBar\DebugBarException
     */
    private function addDebugbar($role_id)
    {
        if (!$role_id || $role_id < User::ROLE_ADMINISTRATOR) {
            return;
        }

        global $debugbar;
        if (!$debugbar) {
            return;
        }

        $rel_url = APP_RELATIVE_URL;
        $debugbar->addCollector(
            new DebugBar\DataCollector\ConfigCollector($this->smarty->tpl_vars, 'Smarty')
        );
        $debugbar->addCollector(
            new DebugBar\DataCollector\ConfigCollector(Setup::get()->toArray(), 'Config')
        );
        $debugbarRenderer = $debugbar->getJavascriptRenderer("{$rel_url}debugbar");
        $debugbarRenderer->addControl(
            'Smarty', array(
                'widget' => 'PhpDebugBar.Widgets.VariableListWidget',
                'map' => 'Smarty',
                'default' => '[]'
            )
        );
        $debugbarRenderer->addControl(
            'Config', array(
                'widget' => 'PhpDebugBar.Widgets.VariableListWidget',
                'map' => 'Config',
                'default' => '[]'
            )
        );

        $this->assign('debugbar_head', $debugbarRenderer->renderHead());
        $this->assign('debugbar_body', $debugbarRenderer->render());
    }
}
