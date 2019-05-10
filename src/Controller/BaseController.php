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

namespace Eventum\Controller;

use Auth;
use Enrise\Uri;
use InvalidArgumentException;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Request;
use Template_Helper;

/**
 * Class BaseController
 *
 * @property-read Helper\AssignHelper $assign
 * @property-read Helper\AttachHelper $attach
 * @property-read Helper\CsrfHelper $csrf
 * @property-read Helper\HtmlHelper $html
 * @property-read Helper\LoggerHelper $logger
 * @property-read Helper\MessagesHelper $messages
 * @property-read Helper\PlotHelper $plot
 * @property-read Helper\RepositoryHelper $repository
 */
abstract class BaseController
{
    /** @var Template_Helper */
    protected $tpl;

    /** @var string */
    protected $tpl_name;

    /** @var bool */
    protected $is_popup = false;

    /**
     * Minimum role required to access the page
     *
     * @var int
     */
    protected $min_role;

    /** @var int */
    protected $role_id;

    /** @var array */
    private $helpers;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->tpl = new Template_Helper($this->tpl_name);
        $this->tpl->enableDebugBar(true);

        $this->configure();
    }

    /**
     * Checks access, invokes defaultAction()
     * and if defaultAction() does not return proper value, throws an exception
     */
    public function run(): void
    {
        // NOTE: canAccess needs $issue_id for the template
        if (!$this->canRoleAccess() || !$this->canAccess()) {
            $this->error(ev_gettext('Sorry, you are not allowed to access this page.'));
        }

        $this->defaultAction();
        $this->prepareTemplate();

        if (!$this->tpl_name) {
            throw new InvalidArgumentException('No template to render');
        }

        $this->displayTemplate();
    }

    protected function getRequest(): Request
    {
        static $request;
        if (!$request) {
            $request = Request::createFromGlobals();
        }

        return $request;
    }

    /**
     * display template
     *
     * @param string $tpl_name
     */
    protected function displayTemplate($tpl_name = null): void
    {
        $this->tpl->assign(
            [
                'messages' => $this->messages->getMessages(),
                'is_popup' => $this->is_popup,
            ]
        );

        // set new template, if needed
        if ($tpl_name) {
            $this->tpl->setTemplate($tpl_name);
        }
        $this->tpl->displayTemplate();
    }

    /**
     * If page is restricted, check for minimum role.
     *
     * @return bool
     */
    final protected function canRoleAccess(): bool
    {
        if ($this->min_role === null) {
            // not restricted
            return true;
        }

        $this->role_id = Auth::getCurrentRole();

        if ($this->is_popup) {
            Auth::checkAuthentication(null, true);
        } else {
            Auth::checkAuthentication();
        }

        return $this->role_id >= $this->min_role;
    }

    /**
     * Display error message $msg and exit
     */
    protected function error(string $msg): void
    {
        $this->messages->addErrorMessage($msg);
        $this->displayTemplate('error_message.tpl.html');
        exit(1);
    }

    /**
     * Redirect to an url with optional GET parameters.
     * This method never returns.
     *
     * @deprecated
     * @see use \Eventum\Controller\Traits\RedirectTrait::redirect
     * @param string $url
     * @param array $params
     * @param bool $allow_external If external urls should be allowed
     */
    protected function redirect($url, $params = [], $allow_external = false): void
    {
        $url = trim($url);
        if ($params) {
            $q = strpos($url, '?') !== false ? '&' : '?';
            $url .= $q . http_build_query($params, null, '&');
        }

        $uri = new Uri($url);
        if (!$allow_external && !$uri->isRelative()) {
            $this->error('Redirecting to the specified URL is not allowed');
        }

        // TODO: drop Auth::redirect once this is only place Auth::redirect is used
        Auth::redirect($url);
    }

    /**
     * Returns TRUE if current request is HTTP POST.
     *
     * @return bool
     * @since 3.1.4
     */
    protected function isPostRequest(): bool
    {
        return $this->getRequest()->isMethod(Request::METHOD_POST);
    }

    public function __get($name)
    {
        $className = 'Eventum\\Controller\\Helper\\' . ucfirst($name) . 'Helper';

        if (!isset($this->helpers[$className])) {
            $this->helpers[$className] = $helper = new $className();

            // clone properties with same name
            $reflectionClass = new ReflectionClass($helper);
            foreach ($reflectionClass->getProperties() as $property) {
                if (property_exists($this, $property->getName())) {
                    $property->setAccessible(true);
                    $property->setValue($helper, $this->{$property->getName()});
                }
            }

            // add Request property
            if ($reflectionClass->hasProperty('request')) {
                $property = $reflectionClass->getProperty('request');
                $property->setAccessible(true);
                $property->setValue($helper, $this->getRequest());
            }
        }

        return $this->helpers[$className];
    }

    /**
     * Create class variables from request.
     * Creating variables that require user to be authenticated, will mostly not work.
     *
     * Use one of these to obtain data from GET/POST or both:
     * $request = $this->getRequest();
     *
     * // obtain from GET, PATH or POST
     * $this->cat = $request->get('cat');
     * // from POST
     * $this->cat = $request->request->get('cat');
     * // from GET
     * $this->cat = $request->query->get('cat');
     * // if you need POST -> GET, then do:
     * $this->cat = $request->request->get('cat') ?: $request->query->get('cat');
     */
    abstract protected function configure();

    /**
     * should return true if method can be accessed
     *
     * @return bool
     */
    abstract protected function canAccess(): bool;

    /**
     * default action of a controller
     * controller may chose sub-actions from there
     */
    abstract protected function defaultAction();

    /**
     * Setup variables needed to render a template.
     */
    abstract protected function prepareTemplate();
}
