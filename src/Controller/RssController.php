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

use APIAuthToken;
use Auth;
use AuthCookie;
use Filter;
use InvalidArgumentException;
use Issue;
use LogicException;
use Misc;
use Project;
use Search;
use Setup;
use User;
use Validation;

class RssController extends BaseController
{
    /** @var string */
    protected $tpl_name = 'rss.tpl.xml';

    /** @var int */
    private $cst_id;

    /** @var int */
    private $usr_id;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $get = $this->getRequest()->query;

        $this->cst_id = $get->getInt('custom_id');
    }

    /**
     * {@inheritdoc}
     */
    protected function canAccess()
    {
        // try current auth cookie
        $this->usr_id = Auth::getUserID();

        if (!$this->usr_id) {
            try {
                $this->authorizeRequest();
            } catch (InvalidArgumentException $e) {
                $this->sendAuthenticateHeader();
                echo 'Error: ', $e->getMessage();
                exit;
            }
        }

        if (!$this->usr_id) {
            // this should not happen
            throw new LogicException('User is not set');
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction()
    {
        // check if the required parameter 'custom_id' is really being passed
        if (!$this->cst_id) {
            $this->rssError("Error: The required 'custom_id' parameter was not provided.");
        }

        // check if the passed 'custom_id' parameter is associated with the usr_id
        if (!Filter::isGlobal($this->cst_id) && !Filter::isOwner($this->cst_id, $this->usr_id)) {
            $this->rssError('Error: The provided custom filter ID is not associated with the given email address.');
        }
    }

    /**
     * Authorize request.
     * TODO: translations
     * TODO: ip based control
     * FIXME: duplicates logic that should be in Auth::checkAuthentication method
     *
     * @throw InvalidArgumentException
     */
    private function authorizeRequest()
    {
        // Setup from HTTP Auth headers
        $request = $this->getRequest();
        $authUser = $request->getUser();
        $authPassword = $request->getPassword();

        if (!$authUser || !$authPassword) {
            throw new InvalidArgumentException(
                'You are required to authenticate in order to access the requested RSS feed.'
            );
        }

        // check the authentication
        if (Validation::isWhitespace($authUser)) {
            throw new InvalidArgumentException('Please provide your email address.');
        }

        if (Validation::isWhitespace($authPassword)) {
            throw new InvalidArgumentException('Please provide your password.');
        }

        // check if user exists
        if (!Auth::userExists($authUser)) {
            throw new InvalidArgumentException('The user specified does not exist.');
        }

        // check if the password matches
        if (!Auth::isCorrectPassword($authUser, $authPassword) && !APIAuthToken::isTokenValidForEmail($authPassword, $authUser)) {
            throw new InvalidArgumentException('The provided email address/password combo is not correct.');
        }

        // check if this user did already confirm his account
        if (Auth::isPendingUser($authUser)) {
            throw new InvalidArgumentException('The provided user still needs to have its account confirmed.');
        }

        // check if this user is really an active one
        if (!Auth::isActiveUser($authUser)) {
            throw new InvalidArgumentException('The provided user is currently set as an inactive user.');
        }

        $this->usr_id = User::getUserIDByEmail($authUser);
        AuthCookie::setAuthCookie($this->usr_id);
    }

    /**
     * Send WWW-Authenticate HTTP header
     */
    private function sendAuthenticateHeader()
    {
        // FIXME: escape tool_caption properly
        header('WWW-Authenticate: Basic realm="' . Misc::getToolCaption() . '"');
        header('HTTP/1.0 401 Unauthorized');
    }

    /**
     * Render error in RSS XML and exit.
     *
     * @param string $msg
     */
    private function rssError($msg)
    {
        header('Content-Type: text/xml; charset=' . APP_CHARSET);

        $this->tpl->assign('error', $msg);
        $this->displayTemplate('rss_error.tpl.xml');
        exit;
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate()
    {
        $filter = Filter::getDetails($this->cst_id, false);

        $options = [
            'users' => $filter['cst_users'],
            'keywords' => $filter['cst_keywords'],
            'priority' => $filter['cst_priorities'],
            'category' => $filter['cst_categories'],
            'status' => $filter['cst_statuses'],
            'hide_closed' => $filter['cst_hide_closed'],
            'sort_by' => $filter['cst_sort_by'],
            'sort_order' => $filter['cst_sort_order'],
            'custom_field' => $filter['cst_custom_field'],
            'search_type' => $filter['cst_search_type'],
        ];

        $issues = Search::getListing($filter['cst_prj_id'], $options, 0, 'ALL');
        $issues = $issues['list'];
        Issue::getDescriptionByIssues($issues);

        $this->tpl->assign(
            [
                'charset' => APP_CHARSET,
                'project_title' => Project::getName($filter['cst_prj_id']),
                'setup' => Setup::get(),
                'filter' => $filter,
                'issues' => $issues,
            ]
        );

        header('Content-Type: text/xml; charset=' . APP_CHARSET);
    }
}
