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

namespace Eventum\Console\Command;

use Eventum\ConcurrentLock;
use Reminder;
use Reminder_Action;
use Reminder_Condition;
use Symfony\Component\Console\Output\OutputInterface;

class ReminderCheckCommand
{
    const DEFAULT_COMMAND = 'reminder:check';
    const USAGE = self::DEFAULT_COMMAND . ' [--debug]';

    /** @var OutputInterface */
    private $output;

    /** @var string */
    private $lock_name = 'check_reminders';

    /** @var int[] */
    private $triggered_issues = [];

    public function execute(OutputInterface $output, $debug)
    {
        $this->output = $output;

        // backward compatible --debug option is same as -vvv
        if ($debug) {
            $output->setVerbosity(OutputInterface::VERBOSITY_DEBUG);
        }

        Reminder::$debug = $output->getVerbosity() === OutputInterface::VERBOSITY_DEBUG;

        $lock = new ConcurrentLock($this->lock_name);
        $lock->synchronized(
            function () {
                $this->checkReminders();
            }
        );
    }

    /**
     * 1 - Get list of reminders with all of its actions
     * 2 - Loop through each reminder level and build the SQL query
     * 3 - If query returns TRUE, then run the appropriate action
     * 4 - Get the list of actions
     * 5 - Calculate which action need to be performed, if any
     * 6 - Avoid repeating reminder actions, so first check if the last triggered action is the same one as "now"
     * 7 - Perform action
     * 8 - Continue to next reminder level
     */
    private function checkReminders()
    {
        $filter = function ($reminder) {
            return $this->filteroutWeekends($reminder);
        };
        $reminders = array_filter(Reminder::getList(), $filter);

        foreach ($reminders as $reminder) {
            $this->processReminder($reminder);
        }
    }

    /**
     * for each action, get the conditions and see if it triggered any issues
     *
     * @param array $reminder
     */
    private function processReminder($reminder)
    {
        foreach ($reminder['actions'] as $action) {
            $message = ev_gettext("Processing Reminder Action '%s'", $action['rma_title']);
            $this->debugMessage($message);

            $conditions = Reminder_Condition::getList($action['rma_id']);
            if (count($conditions) == 0) {
                $message = '  - ' . ev_gettext('Skipping Reminder because there were no reminder conditions found');
                $this->debugMessage($message);
                continue;
            }

            $issues = Reminder::getTriggeredIssues($reminder, $conditions);
            // avoid repeating reminder actions, so get the list of issues
            // that were last triggered with this reminder action ID
            $repeat_issues = Reminder_Action::getRepeatActions($issues, $action['rma_id']);
            if (count($repeat_issues) > 0) {
                // add the repeated issues to the list of already triggered
                // issues, so they get ignored for the next reminder actions
                foreach ($repeat_issues as $issue_id) {
                    $message = '  - ' . ev_gettext("Adding repeated issue '%d' to the list of already triggered issues", $issue_id);
                    $this->debugMessage($message);

                    $this->triggered_issues[] = $issue_id;
                }
            }

            if (count($issues) == 0) {
                $message = '  - ' . ev_gettext("No triggered issues for action '%s'", $action['rma_title']);
                $this->debugMessage($message);
                continue;
            }

            $this->performActions($reminder, $action, $issues);
        }
    }

    private function performActions($reminder, $action, $issues)
    {
        foreach ($issues as $issue_id) {
            $message = '  - ' . ev_gettext('Processing issue #%d', $issue_id);
            $this->debugMessage($message);

            // only perform one action per issue id
            if (in_array($issue_id, $this->triggered_issues)) {
                $message = '  - ' . ev_gettext('Ignoring issue #%d because it was found in the list of already triggered issues', $issue_id);
                $this->debugMessage($message);

                continue;
            }

            $this->triggered_issues[] = $issue_id;
            $message = '  - ' . ev_gettext("Triggered Action '%s' for issue #%d", $action['rma_title'], $issue_id);
            $this->debugMessage($message);

            Reminder_Action::perform($issue_id, $reminder, $action);
        }
    }

    /**
     * if this is the weekend and this reminder isn't supposed to run on weekends skip
     */
    private function filteroutWeekends($reminder)
    {
        $weekday = date('w');

        // if this is the weekend and this reminder isn't supposed to run on weekends skip
        if ($reminder['rem_skip_weekend'] == 1 && in_array($weekday, [0, 6])) {
            $message = ev_gettext("Skipping Reminder '%s' due to weekend exclusion", $reminder['rem_title']);
            $this->debugMessage($message);

            return false;
        }

        return true;
    }

    private function debugMessage($message)
    {
        $this->output->writeln($message, OutputInterface::VERBOSITY_DEBUG);
    }
}
