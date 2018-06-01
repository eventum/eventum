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
use Issue;

class ValidateController extends BaseController
{
    /** @var string */
    private $action;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $request = $this->getRequest();

        $this->action = (string) $request->get('action');
    }

    /**
     * {@inheritdoc}
     */
    protected function canAccess()
    {
        Auth::checkAuthentication();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction()
    {
        switch ($this->action) {
            case 'validateIssueNumbers':
                echo $this->validateIssueNumbersAction();
                break;

            default:
                printf('ERROR: Unable to call function %s', htmlspecialchars($this->action));
        }
        exit;
    }

    private function validateIssueNumbersAction()
    {
        $request = $this->getRequest();

        $issues = filter_var_array(explode(',', $request->get('values')), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $check_project = $request->get('check_project') != 0;
        $exclude_issue = $request->get('exclude_issue');
        $exclude_duplicates = $request->get('exclude_duplicates') == 1;

        $bad_issues = [];
        foreach ($issues as $issue_id) {
            if ($exclude_issue == $issue_id
                || ($issue_id != '' && !Issue::exists($issue_id, $check_project))
                || ($exclude_duplicates && Issue::isDuplicate($issue_id))
            ) {
                $bad_issues[] = htmlspecialchars($issue_id);
            }
        }

        if ($bad_issues) {
            return implode(', ', $bad_issues);
        }

        return 'ok';
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate()
    {
    }
}
