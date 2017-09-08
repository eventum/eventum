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

namespace Eventum\Command;

use Eventum\ConcurrentLock;
use Reminder;
use Reminder_Action;
use Reminder_Condition;
use Symfony\Component\Console\Output\OutputInterface;

class CheckRemindersCommand
{
    const DEFAULT_COMMAND = 'reminders:check';
    const USAGE = self::DEFAULT_COMMAND . ' [--debug]';

    /** @var OutputInterface */
    private $output;

    /** @var string */
    private $lock_name = 'check_reminders';

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
        $triggered_issues = [];

        $reminders = Reminder::getList();

        $reminders = array_filter($reminders, function ($reminder) {
            return $this->filteroutWeekends($reminder);
        });

        foreach ($reminders as $reminder) {
            // for each action, get the conditions and see if it triggered any issues
            foreach ($reminder['actions'] as $action) {
                $message = "Processing Reminder Action '{$action['rma_title']}'";
                $this->debugMessage($message);

                $conditions = Reminder_Condition::getList($action['rma_id']);
                if (count($conditions) == 0) {
                    $message = '  - Skipping Reminder because there were no reminder conditions found';
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
                    foreach ($repeat_issues as $issue) {
                        $message = "  - Adding repeated issue '{$issue}' to the list of already triggered issues";
                        $this->debugMessage($message);

                        $triggered_issues[] = $issue;
                    }
                }
                if (count($issues) > 0) {
                    foreach ($issues as $issue) {
                        $message = "  - Processing issue '{$issue}";
                        $this->debugMessage($message);

                        // only perform one action per issue id
                        if (in_array($issue, $triggered_issues)) {
                            $message = "  - Ignoring issue '{$issue}' because it was found in the list of already triggered issues\n";
                            $this->debugMessage($message);

                            continue;
                        }
                        $triggered_issues[] = $issue;
                        $message = "  - Triggered Action '{$action['rma_title']}' for issue #{$issue}\n";
                        $this->debugMessage($message);

                        Reminder_Action::perform($issue, $reminder, $action);
                    }
                } else {
                    $message = "  - No triggered issues for action '{$action['rma_title']}'";
                    $this->debugMessage($message);
                }
            }
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
            $message = "Skipping Reminder '{$reminder['rem_title']}' due to weekend exclusion";
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
