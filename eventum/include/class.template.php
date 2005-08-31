<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004, 2005 MySQL AB                              |
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
//
// @(#) $Id: s.class.template.php 1.34 03/12/31 17:29:01-00:00 jpradomaia $
//

require_once(APP_PEAR_PATH . "Net/UserAgent/Detect.php");
require_once(APP_SMARTY_PATH . "Smarty.class.php");
require_once(APP_INC_PATH . "class.project.php");
require_once(APP_INC_PATH . "class.auth.php");
require_once(APP_INC_PATH . "class.user.php");
require_once(APP_INC_PATH . "class.setup.php");
require_once(APP_INC_PATH . "class.link_filter.php");
require_once(APP_INC_PATH . "class.custom_field.php");

/**
 * Class used to abstract the backend template system used by the site. This
 * is especially useful to be able to change template backends in the future
 * without having to rewrite all PHP based scripts.
 *
 * @version 1.0
 * @author João Prado Maia <jpm@mysql.com>
 */

class Template_API
{
    var $smarty;
    var $tpl_name = "";

    /**
     * Constructor of the class
     *
     * @access public
     */
    function Template_API()
    {
        $this->smarty = new Smarty;
        $this->smarty->template_dir = APP_TPL_PATH . APP_CURRENT_LANG;
        $this->smarty->compile_dir = APP_PATH . "templates_c";
        $this->smarty->config_dir = '';
        $this->smarty->register_modifier("activateLinks", array('Link_Filter', 'activateLinks'));
        $this->smarty->register_modifier("formatCustomValue", array('Custom_Field', 'formatValue'));
    }


    /**
     * Sets the internal template filename for the current PHP script
     *
     * @access public
     * @param  string $tpl_name The filename of the template
     */
    function setTemplate($tpl_name)
    {
        $this->tpl_name = $tpl_name;
    }


    /**
     * Assigns variables to specific placeholders on the target template
     *
     * @access public
     * @param  string $var_name Placeholder on the template
     * @param  string $value Value to be assigned to this placeholder
     */
    function assign($var_name, $value = "")
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
    function bulkAssign($array)
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
    function displayTemplate()
    {
        if (APP_BENCHMARK) {
            // stop the benchmarking
            $GLOBALS["bench"]->stop();
            $profiling = $GLOBALS["bench"]->getProfiling();
            // last minute check on the benchmarking results
            $this->assign(array("benchmark_total" => sprintf("%.4f", $profiling[count($profiling)-1]["total"]),
                                "benchmark_results" => base64_encode(serialize($profiling))));
        }
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
    function getTemplateContents()
    {
        $this->processTemplate();
        return $this->smarty->fetch($this->tpl_name);
    }


    /**
     * Processes the template and assigns common variables automatically.
     * 
     * @access	private
     */
    function processTemplate()
    {
        global $HTTP_SERVER_VARS;

        // determine the correct CSS file to use
        if (ereg('MSIE ([0-9].[0-9]{1,2})', @$HTTP_SERVER_VARS["HTTP_USER_AGENT"], $log_version)) {
            $user_agent = 'ie';
        } else {
            $user_agent = 'other';
        }
        $this->assign("user_agent", $user_agent);
        // create the list of projects
        $usr_id = Auth::getUserID();
        if ($usr_id != '') {
            $prj_id = Auth::getCurrentProject();
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
            $this->assign("active_projects", Project::getAssocList($usr_id));
            $this->assign("current_full_name", $info["usr_full_name"]);
            $this->assign("current_email", $info["usr_email"]);
            $this->assign("current_user_id", $usr_id);
            $this->assign("is_current_user_clocked_in", User::isClockedIn($usr_id));
            $this->assign("roles", User::getAssocRoleIDs());
        }
        $this->assign("app_setup", Setup::load());
        $this->assign("app_setup_path", APP_SETUP_PATH);
        $this->assign("app_setup_file", APP_SETUP_FILE);

        $this->assign("application_version", APP_VERSION);
        $this->assign("application_title", APP_NAME);
        $this->assign("app_base_url", APP_BASE_URL);
        $this->assign("rel_url", APP_RELATIVE_URL);
        $this->assign("lang", APP_CURRENT_LANG);
        $this->assign("SID", SID);

        // now for the browser detection stuff
        Net_UserAgent_Detect::detect();
        $this->assign("browser", Net_UserAgent_Detect::_getStaticProperty('browser'));
        $this->assign("os", Net_UserAgent_Detect::_getStaticProperty('os'));

        // this is only used by the textarea resize script
        $js_script_name = str_replace('/', '_', str_replace('.php', '', $HTTP_SERVER_VARS['PHP_SELF']));
        $this->assign("js_script_name", $js_script_name);

        $this->assign("total_queries", $GLOBALS['TOTAL_QUERIES']);

        $this->assign(array(
            "cell_color"     => APP_CELL_COLOR,
            "light_color"    => APP_LIGHT_COLOR,
            "middle_color"   => APP_MIDDLE_COLOR,
            "dark_color"     => APP_DARK_COLOR,
            "cycle"          => APP_CYCLE_COLORS,
            "internal_color" => APP_INTERNAL_COLOR
        ));
    }
}

// benchmarking the included file (aka setup time)
if (APP_BENCHMARK) {
    $GLOBALS['bench']->setMarker('Included Template Class');
}
?>
