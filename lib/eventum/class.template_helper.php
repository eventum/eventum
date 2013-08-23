<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2013 Eventum Team.                              |
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
// | 59 Temple Place - Suite 330                                          |
// | Boston, MA 02111-1307, USA.                                          |
// +----------------------------------------------------------------------+
// | Authors: João Prado Maia <jpm@mysql.com>                             |
// +----------------------------------------------------------------------+

require_once 'Net/UserAgent/Detect.php';
require_once APP_SMARTY_PATH . '/Smarty.class.php';

/**
 * Class used to abstract the backend template system used by the site. This
 * is especially useful to be able to change template backends in the future
 * without having to rewrite all PHP based scripts.
 *
 * @version 1.0
 * @author João Prado Maia <jpm@mysql.com>
 */

class Template_Helper
{
    var $smarty;
    var $tpl_name = "";

    /**
     * Constructor of the class
     *
     * @access public
     */
    function Template_Helper()
    {
        $this->smarty = new Smarty;
        $this->smarty->template_dir = APP_TPL_PATH;
        $this->smarty->compile_dir = APP_TPL_COMPILE_PATH;
        $this->smarty->plugins_dir  = array(APP_INC_PATH . '/smarty', 'plugins');
        $this->smarty->config_dir = '';
        $this->smarty->register_modifier("activateLinks", array('Link_Filter', 'activateLinks'));
        $this->smarty->register_modifier("activateAttachmentLinks", array('Link_Filter', 'activateAttachmentLinks'));
        $this->smarty->register_modifier("formatCustomValue", array('Custom_Field', 'formatValue'));
    }


    /**
     * Sets the internal template filename for the current PHP script
     *
     * @param  string $tpl_name The filename of the template
     */
    public function setTemplate($tpl_name)
    {
        $this->tpl_name = $tpl_name;
    }


    /**
     * Assigns variables to specific placeholders on the target template
     *
     * @param  string $var_name Placeholder on the template
     * @param  string $value Value to be assigned to this placeholder
     */
    public function assign($var_name, $value = "")
    {
        if (!is_array($var_name)) {
            $this->smarty->assign($var_name, $value);
        } else {
            $this->smarty->assign($var_name);
        }
    }


    /**
     * Assigns variables to specific placeholders on the target template
     *
     * @access public
     * @param  array $array Array with the PLACEHOLDER=>VALUE pairs to be assigned
     */
    public function bulkAssign($array)
    {
        while (list($key, $value) = each($array)) {
            $this->smarty->assign($key, $value);
        }
    }


    /**
     * Prints the actual parsed template.
     *
     * @access public
     */
    public function displayTemplate()
    {
        $this->processTemplate();
        // finally display the parsed template
        $this->smarty->display($this->tpl_name);
    }


    /**
     * Returns the contents of the parsed template
     *
     * @access public
     * @return string The contents of the parsed template
     */
    public function getTemplateContents()
    {
        $this->processTemplate();
        return $this->smarty->fetch($this->tpl_name);
    }


    /**
     * Processes the template and assigns common variables automatically.
     *
     * @access    private
     */
    function processTemplate()
    {
        // determine the correct CSS file to use
        if (preg_match('/MSIE ([0-9].[0-9]{1,2})/', @$_SERVER["HTTP_USER_AGENT"], $log_version)) {
            $user_agent = 'ie';
        } else {
            $user_agent = 'other';
        }
        $this->assign("user_agent", $user_agent);
        // create the list of projects
        $usr_id = Auth::getUserID();
        if ($usr_id != '') {
            $prj_id = Auth::getCurrentProject();  
            $setup = Setup::load();
            if (!empty($prj_id)) {
                $role_id = User::getRoleByUser($usr_id, $prj_id);
                $this->assign("current_project", $prj_id);
                $this->assign("current_project_name", Auth::getCurrentProjectName());
                $has_customer_integration = Customer::hasCustomerIntegration($prj_id);
                $this->assign("has_customer_integration", $has_customer_integration);
                if ($has_customer_integration) {
                    $this->assign("customer_backend_name", Customer::getBackendImplementationName($prj_id));
                }
                if (($role_id == User::getRoleID('administrator')) || ($role_id == User::getRoleID('manager'))) {
                    $this->assign("show_admin_link", true);
                }
                if ($role_id > 0) {
                    $this->assign("current_role", (integer) $role_id);
                    $this->assign("current_role_name", User::getRole($role_id));
                }
            }
            $info = User::getNameEmail($usr_id);
            $raw_projects = Project::getAssocList(Auth::getUserID(), false, true);
            $active_projects = array();
            foreach ($raw_projects as $prj_id => $prj_info) {
                if ($prj_info['status'] == 'archived') {
                    $prj_info['prj_title'] .= ' ' . ev_gettext('(archived)');
                }
                $active_projects[$prj_id] = $prj_info['prj_title'];
            }
            $this->assign("active_projects", $active_projects);
            $this->assign("current_full_name", $info["usr_full_name"]);
            $this->assign("current_email", $info["usr_email"]);
            $this->assign("current_user_id", $usr_id);
            $this->assign("handle_clock_in", $setup['handle_clock_in'] == 'enabled');
            $this->assign("is_current_user_clocked_in", User::isClockedIn($usr_id));
            $this->assign("is_anon_user", Auth::isAnonUser());
            $this->assign("roles", User::getAssocRoleIDs());
        }
        $this->assign('app_path', APP_PATH);
        $this->assign("app_setup", Setup::load());
        $this->assign("app_config_path", APP_CONFIG_PATH);
        $this->assign("app_setup_file", APP_SETUP_FILE);

        $this->assign("application_version", APP_VERSION);
        $this->assign("application_title", APP_NAME);
        $this->assign("app_base_url", APP_BASE_URL);
        $this->assign("rel_url", APP_RELATIVE_URL);

        // now for the browser detection stuff
        Net_UserAgent_Detect::detect();
        $this->assign("browser", Net_UserAgent_Detect::_getStaticProperty('browser'));
        $this->assign("os", Net_UserAgent_Detect::_getStaticProperty('os'));

        // this is only used by the textarea resize script
        $js_script_name = str_replace('/', '_', str_replace('.php', '', $_SERVER['PHP_SELF']));
        $this->assign("js_script_name", $js_script_name);

        $this->assign(array(
            "cell_color"     => APP_CELL_COLOR,
            "light_color"    => APP_LIGHT_COLOR,
            "middle_color"   => APP_MIDDLE_COLOR,
            "dark_color"     => APP_DARK_COLOR,
            "cycle"          => APP_CYCLE_COLORS,
            "internal_color" => APP_INTERNAL_COLOR
        ));

        $this->assign("app_auth_backend", APP_AUTH_BACKEND);

        $this->assign('app_messages', Misc::getMessages());
    }
}
