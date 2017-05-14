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

use Eventum\DebugBar;
use Eventum\Templating;

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
        $smarty->setTemplateDir([APP_LOCAL_PATH . '/templates', APP_TPL_PATH]);
        $smarty->setCompileDir(APP_TPL_COMPILE_PATH);

        $smarty->addPluginsDir([APP_INC_PATH . '/smarty']);

        $smarty->registerPlugin('modifier', 'activateLinks', [Link_Filter::class, 'activateLinks']);
        $smarty->registerPlugin('modifier', 'activateAttachmentLinks', [Link_Filter::class, 'activateAttachmentLinks']);
        $smarty->registerPlugin('modifier', 'formatCustomValue', [Custom_Field::class, 'formatValue']);
        $smarty->registerPlugin('modifier', 'bool', [Misc::class, 'getBooleanDisplayValue']);
        $smarty->registerPlugin('modifier', 'format_date', [Date_Helper::class, 'getFormattedDate']);
        $smarty->registerPlugin('modifier', 'timeago', [Date_Helper::class, 'formatTimeAgo']);
        $smarty->registerPlugin('modifier', 'format_email', [Eventum\EmailHelper::class, 'formatEmail']);

        // Fixes problem with CRM API and dynamic includes.
        // See https://github.com/smarty-php/smarty/blob/v3.1.16/3.1.16_RELEASE_NOTES.txt
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
     * @param  string|string[] $var_name Placeholder on the template
     * @param  string|array $value Value to be assigned to this placeholder
     * @return $this
     */
    public function assign($var_name, $value = null)
    {
        $this->smarty->assign($var_name, $value);

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
            $hash = file_get_contents($file);
            // it can be branch:
            // "ref: refs/heads/master"
            // or some tag:
            // "fc334abadfd480820071c1415723c7de0216eb6f"
            if (substr($hash, 0, 4) == 'ref:') {
                list(, $refname) = explode(': ', $hash);
                if (!file_exists($file = APP_PATH . '/.git/' . trim($refname))) {
                    return null;
                }
                $hash = file_get_contents($file);
            }

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
        $setup = Setup::get();
        $core = [
            'rel_url' => APP_RELATIVE_URL,
            'base_url' => APP_BASE_URL,
            'app_title' => APP_NAME,
            'app_version' => APP_VERSION,
            'app_setup' => Setup::get(),
            'roles' => User::getAssocRoleIDs(),
            // @deprecated: do not use 'current_url'
            // @see https://github.com/eventum/eventum/pull/265
            'current_url' => $_SERVER['PHP_SELF'],
            'template_id' => str_replace(['/', '.tpl.html'], ['_'], $this->tpl_name),
            'handle_clock_in' => $setup['handle_clock_in'] == 'enabled',
        ];

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
            if (!empty($prj_id)) {
                $role_id = User::getRoleByUser($usr_id, $prj_id);
                $has_crm = CRM::hasCustomerIntegration($prj_id);
                $core = $core + [
                        'project_id' => $prj_id,
                        'project_name' => Auth::getCurrentProjectName(),
                        'has_crm' => $has_crm,
                        'current_role' => $role_id,
                        'current_role_name' => User::getRole($role_id),
                        'feature_access' => Access::getFeatureAccessArray($usr_id),
                    ];
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
            $raw_projects = Project::getAssocList($usr_id, false, true);
            $active_projects = [];
            foreach ($raw_projects as $prj_id => $prj_info) {
                if ($prj_info['status'] == 'archived') {
                    $prj_info['prj_title'] .= ' ' . ev_gettext('(archived)');
                }
                $active_projects[$prj_id] = $prj_info['prj_title'];
            }
            $core += [
                    'active_projects' => $active_projects,
                    'current_full_name' => $info['usr_full_name'],
                    'current_email' => $info['usr_email'],
                    'current_user_id' => $usr_id,
                    'current_user_datetime' => Date_Helper::getISO8601date('now', '', true),
                    'is_current_user_clocked_in' => User::isClockedIn($usr_id),
                    'is_anon_user' => Auth::isAnonUser(),
                    'is_current_user_partner' => !empty($info['usr_par_code']),
                    'current_user_prefs' => Prefs::get($usr_id),
                ];
        }
        $this->assign('core', $core);

        $userfile = new Templating\UserFile($this->smarty, APP_LOCAL_PATH);
        $userfile();

        if (isset($role_id) && $role_id >= User::ROLE_ADMINISTRATOR) {
            DebugBar::register($this->smarty);
        }

        return $this;
    }
}
