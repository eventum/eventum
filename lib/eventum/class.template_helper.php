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

use Eventum\AppInfo;
use Eventum\Config\Paths;
use Eventum\DebugBarManager;
use Eventum\Event\SystemEvents;
use Eventum\EventDispatcher\EventManager;
use Eventum\ServiceContainer;
use Eventum\Templating;

/**
 * Class used to abstract the backend template system used by the site. This
 * is especially useful to be able to change template backends in the future
 * without having to rewrite all PHP based scripts.
 */
class Template_Helper
{
    private const TEMPLATE_CACHE_DIR = Paths::APP_TPL_COMPILE_PATH;
    /** @var Smarty */
    private $smarty;

    /** @var string */
    private $tpl_name;
    /** @var bool */
    private $debugBarEnabled = false;

    /**
     * Templates to inject to header
     */
    private $headerTemplates = [];

    /**
     * @param string $templateName
     * @throws SmartyException
     */
    public function __construct(string $templateName = null)
    {
        $smarty = new Smarty();
        $smarty->setTemplateDir(Setup::getTemplatePaths());
        $smarty->setCompileDir(Misc::ensureDir(self::TEMPLATE_CACHE_DIR));

        $smarty->addPluginsDir([Paths::APP_INC_PATH . '/smarty']);

        $smarty->registerPlugin('modifier', 'activateLinks', [Link_Filter::class, 'activateLinks']);
        $smarty->registerPlugin('modifier', 'activateAttachmentLinks', [Link_Filter::class, 'activateAttachmentLinks']);
        $smarty->registerPlugin('modifier', 'bool', [Misc::class, 'getBooleanDisplayValue']);
        $smarty->registerPlugin('modifier', 'format_date', [Date_Helper::class, 'getFormattedDate']);
        $smarty->registerPlugin('modifier', 'timeago', [Date_Helper::class, 'formatTimeAgo']);
        $smarty->registerPlugin('modifier', 'format_email', [Eventum\EmailHelper::class, 'formatEmail']);
        $smarty->registerPlugin('modifier', 'textFormat', [Link_Filter::class, 'textFormat']);
        $asset = Templating\Asset::create();
        $smarty->registerPlugin('function', 'asset', static function (array $args) use ($asset) {
            $path = $args['path'] ?? null;

            return $asset->getUrl($path);
        });

        // Fixes problem with CRM API and dynamic includes.
        // See https://github.com/smarty-php/smarty/blob/v3.1.16/3.1.16_RELEASE_NOTES.txt
        if (isset($smarty->inheritance_merge_compiled_includes)) {
            $smarty->inheritance_merge_compiled_includes = false;
        }

        // this avoids loading it twice when using composer
        if (function_exists('smarty_block_t')) {
            $smarty->registerPlugin('block', 't', 'smarty_block_t');
        }

        if ($templateName) {
            $this->setTemplate($templateName);
        }

        $this->smarty = $smarty;
    }

    /**
     * Sets the internal template filename for the current PHP script
     *
     * @param  string $tpl_name The filename of the template
     */
    public function setTemplate($tpl_name): self
    {
        $this->tpl_name = $tpl_name;

        return $this;
    }

    /**
     * Assigns variables to specific placeholders on the target template
     *
     * @param  string|string[] $var_name Placeholder on the template
     * @param  string|array $value Value to be assigned to this placeholder
     */
    public function assign($var_name, $value = null): self
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
    public function displayTemplate(bool $process = true): self
    {
        if ($process) {
            $this->processTemplate();
        }

        $this->smarty->display($this->tpl_name);

        return $this;
    }

    /**
     * Returns the contents of the parsed template
     *
     * @param bool $process Whether to call process template to fill template variables. Default true
     * @return string The contents of the parsed template
     */
    public function getTemplateContents(bool $process = true): string
    {
        if ($process) {
            $this->processTemplate();
        }

        return $this->smarty->fetch($this->tpl_name);
    }

    /**
     * Processes the template and assign common variables automatically.
     */
    private function processTemplate(): self
    {
        $setup = ServiceContainer::getConfig();
        $appInfo = AppInfo::getInstance();
        $core = [
            'rel_url' => Setup::getRelativeUrl(),
            'base_url' => Setup::getBaseUrl(),
            'app_title' => Setup::getAppName(),
            'app_version' => $appInfo->getVersion(),
            'app_version_link' => $appInfo->getVersionLink(),
            'app_setup' => ServiceContainer::getConfig(),
            'roles' => User::getAssocRoleIDs(),
            'template_id' => str_replace(['/', '.tpl.html'], ['_'], $this->tpl_name),
            'handle_clock_in' => $setup['handle_clock_in'] === 'enabled',
        ];

        $usr_id = Auth::getUserID();
        if ($usr_id) {
            $core['user'] = User::getDetails($usr_id);
            $prj_id = Auth::getCurrentProject();
            if (!empty($prj_id)) {
                $role_id = User::getRoleByUser($usr_id, $prj_id);
                $has_crm = CRM::hasCustomerIntegration($prj_id);
                $core += [
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
                if ($prj_info['status'] === 'archived') {
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

        EventManager::dispatch(SystemEvents::SMARTY_PROCESS, $this);

        $this->assign('header_templates', $this->headerTemplates);

        $userFile = new Templating\UserFile($this->smarty, ServiceContainer::getConfig()['local_path']);
        $userFile();

        if ($this->debugBarEnabled && isset($role_id) && $role_id >= User::ROLE_ADMINISTRATOR) {
            DebugBarManager::getDebugBarManager()->registerSmarty($this->smarty);
        }

        return $this;
    }

    public function addHeaderTemplate(string $template): void
    {
        $this->headerTemplates[] = $template;
    }

    public function enableDebugBar(bool $enable): void
    {
        $this->debugBarEnabled = $enable;
    }
}
