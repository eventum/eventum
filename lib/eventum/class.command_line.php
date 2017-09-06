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

use Eventum\RPC\RemoteApi;

class Command_Line
{
    private static $_displayed_confirmation = null;

    /**
     * Prompts the user for a resolution option, and returns the ID of the
     * selected one.
     *
     * @param   RemoteApi $client The connection resource
     * @return  int The selected resolution id
     */
    public function promptResolutionSelection($client)
    {
        $list = $client->getResolutionAssocList();

        if (count($list) > 1) {
            // need to ask which status this person wants to use
            $prompt = "Which resolution do you want to use in this action?\n";
            foreach ($list as $key => $value) {
                $prompt .= sprintf(" [%s] => %s\n", $key, $value);
            }
            $prompt .= 'Please enter the resolution';
            $resolution_id = CLI_Misc::prompt($prompt, false);
            $available_ids = array_keys($list);
            if (!in_array($resolution_id, $available_ids)) {
                self::quit("Entered resolution doesn't match any in the list available to you");
            }
        } else {
            if (count($list) == 0) {
                $resolution_id = 0;
            } else {
                $t = array_keys($list);
                $resolution_id = $t[0];
            }
        }

        return $resolution_id;
    }

    /**
     * Prompts the user for a status option, and returns the title of the
     * selected one.
     *
     * @param   RemoteApi $client The connection resource
     * @param   array $auth Array of authentication information (email, password)
     * @param   int $prj_id The project ID
     * @return  string The selected status title
     */
    public function promptStatusSelection($client, $auth, $prj_id)
    {
        $list = $client->getClosedAbbreviationAssocList($auth[0], $auth[1], (int) $prj_id);

        if (count($list) > 1) {
            // need to ask which status this person wants to use
            $prompt = "Which status do you want to use in this action?\n";
            foreach ($list as $key => $value) {
                $prompt .= sprintf(" [%s] => %s\n", $key, $value);
            }
            $prompt .= 'Please enter the status';
            $status = CLI_Misc::prompt($prompt, false);
            $lowercase_keys = Misc::lowercase(array_keys($list));
            $lowercase_values = Misc::lowercase(array_values($list));

            if ((!in_array(strtolower($status), $lowercase_keys)) &&
                    (!in_array(strtolower($status), $lowercase_values))) {
                self::quit("Entered status doesn't match any in the list available to you");
            } else {
                if (in_array(strtolower($status), $lowercase_keys)) {
                    $status_title = $list[strtoupper($status)];
                } else {
                    $status_title = $status;
                }
            }
        } else {
            $t = array_values($list);
            $status_title = $t[0];
        }

        return $status_title;
    }

    /**
     * Marks an issue as closed.
     *
     * @param   RemoteApi $client The connection resource
     * @param   array $auth Array of authentication information (email, password)
     * @param   int $issue_id The issue ID
     */
    public static function closeIssue($client, $auth, $issue_id)
    {
        $details = self::checkIssuePermissions($client, $auth, $issue_id);
        self::checkIssueAssignment($client, $auth, $issue_id);

        // prompt for status selection (accept abbreviations)
        $new_status = self::promptStatusSelection($client, $auth, $details['iss_prj_id']);
        // check if the issue already is set to the new status
        if ((strtolower($details['sta_title']) == strtolower($new_status)) ||
                (strtolower($details['sta_abbreviation']) == strtolower($new_status))) {
            self::quit("Issue #$issue_id is already set to status '" . $details['sta_title'] . "'");
        }

        // prompt for status selection (accept abbreviations)
        $resolution_id = self::promptResolutionSelection($client);

        // ask whether to send a notification email about this action or not (defaults to yes)
        $msg = 'Would you like to send a notification email about this issue being closed? [y/n]';
        $ret = CLI_Misc::prompt($msg, false);
        if (strtolower($ret) == 'y') {
            $send_notification = true;
        } else {
            $send_notification = false;
        }

        // prompt for internal note
        $prompt = 'Please enter a reason for closing this issue (one line only)';
        $note = CLI_Misc::prompt($prompt, false);

        $result = $client->closeIssue($auth[0], $auth[1], $issue_id, $new_status, (int) $resolution_id, $send_notification, $note);

        echo "OK - Issue #$issue_id successfully closed.\n";

        if ($result == 'INCIDENT') {
            echo "WARNING: This customer has incidents. Please redeem incidents by running 'eventum $issue_id redeem'\n";
        }
    }

    /**
     * Looks up customer information given a set of search parameters.
     *
     * @param   RemoteApi $client The connection resource
     * @param   array $auth Array of authentication information (email, password)
     * @param   string $field The field in which to search
     * @param   string $value The value to search against
     */
    public static function lookupCustomer($client, $auth, $field, $value)
    {
        $project_id = self::promptProjectSelection($client, $auth, true);

        $res = $client->lookupCustomer($auth[0], $auth[1], $project_id, $field, $value);

        if (!is_array($res)) {
            echo "ERROR: Sorry, for security reasons you need to wait $res until your next customer lookup.\n";

            return;
        }

        if (count($res) == 0) {
            echo "Sorry, no customers could be found.\n";

            return;
        }

        $out = [];
        $out[] = "Customer Lookup Results:\n";
        foreach ($res as $customer) {
            $out[] = '         Customer: ' . $customer['name'];
            $out[] = '    Support Level: ' . $customer['contract']['support_level'];
            $out[] = '       Expiration: ' . $customer['contract']['end_date'];
            $out[] = '  Contract Status: ' . $customer['contract']['status'];
            // contacts now...
            if (count($customer['contract']['contacts']) > 0) {
                foreach ($customer['contract']['contacts'] as $contact) {
                    $out[] = '                   ' . $contact['name'] . ' - ' . $contact['email'] .
                        (empty($contact['phone']) ? '' : (' - ' . $contact['phone']));
                }
            }
            $out[] = "\n";
        }
        echo implode("\n", $out);
    }

    /**
     * Method used to parse the eventum command line configuration file
     * and return the appropriate configuration settings.
     *
     * @return  array The configuration settings
     */
    public static function getEnvironmentSettings()
    {
        $rcfile = getenv('HOME') . '/.eventumrc';

        $email = '';
        $password = '';
        $host = '';
        $port = '';
        $relative_url = '';
        if (file_exists($rcfile)) {
            $fp = fopen($rcfile, 'r');
            if (!$fp) {
                die("Couldn't open eventum rcfile '$rcfile'\n");
            }
            $lines = explode("\n", fread($fp, filesize($rcfile)));
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) {
                    continue;
                }
                $var = trim(substr($line, 0, strpos($line, '=')));
                $value = trim(substr($line, strpos($line, '=') + 1));
                if ($var == 'EVENTUM_USER') {
                    $email = $value;
                } elseif ($var == 'EVENTUM_PASSWORD') {
                    $password = $value;
                } elseif ($var == 'EVENTUM_HOST') {
                    $host = $value;
                } elseif ($var == 'EVENTUM_PORT') {
                    $port = $value;
                } elseif ($var == 'EVENTUM_RELATIVE_URL') {
                    $relative_url = $value;
                }
            }
        } else {
            die("Configuration file '$rcfile' could not be found\n");
        }

        return [$email, $password, $host, $port, $relative_url];
    }

    /**
     * Prints out a list of attachments associated with the given issue ID.
     *
     * @param   RemoteApi $client The connection resource
     * @param   array $auth Array of authentication information (email, password)
     * @param   int $issue_id The issue ID
     */
    public static function printFileList($client, $auth, $issue_id)
    {
        self::checkIssuePermissions($client, $auth, $issue_id);

        $list = $client->getFileList($auth[0], $auth[1], $issue_id);

        $i = 1;
        foreach ($list as $attachment) {
            echo "--------------------------------------------------------------\n";
            $iat_created_date = Date_Helper::getFormattedDate($attachment['iat_created_date']);
            echo ' Attachment sent by ' . $attachment['usr_full_name'] . ' on ' . $iat_created_date . "\n";
            foreach ($attachment['files'] as $file) {
                echo "  [$i] => " . $file['iaf_filename'] . ' (' . $file['iaf_filesize'] . ")\n";
                $i++;
            }
            echo ' Description: ' . $attachment['iat_description'] . "\n";
        }
    }

    /**
     * Downloads a given attachment file number.
     *
     * @param   RemoteApi $client The connection resource
     * @param   array $auth Array of authentication information (email, password)
     * @param   int $issue_id The issue ID
     * @param   int $file_number The attachment file number
     */
    public static function getFile($client, $auth, $issue_id, $file_number)
    {
        self::checkIssuePermissions($client, $auth, $issue_id);

        // check if the provided file number is valid
        $list = $client->getFileList($auth[0], $auth[1], $issue_id);

        $file_id = 0;
        $i = 1;
        foreach ($list as $attachment) {
            foreach ($attachment['files'] as $file) {
                if ($file_number == $i) {
                    $file_id = $file['iaf_id'];
                }
                $i++;
            }
        }
        if (empty($file_id)) {
            self::quit("Unknown file number #$file_number. Please review the list of available files with 'list-files'");
        }

        echo "Downloading file #$file_number from issue $issue_id...\n";

        $details = $client->getFile($auth[0], $auth[1], (int) $file_id);

        // check if the file already exists
        if (file_exists($details['name'])) {
            $msg = "The requested file ('" . $details['name'] . "') already exists in the current directory. Would you like to overwrite this file? [y/n]";
            $ret = CLI_Misc::prompt($msg, false);
            if (strtolower($ret) == 'y') {
                unlink($details['name']);
                if (file_exists($details['name'])) {
                    self::quit('No permission to remove the file');
                }
            } else {
                self::quit('Download halted');
            }
        }
        $fp = fopen($details['name'], 'w');
        fwrite($fp, base64_decode($details['contents']));
        fclose($fp);
        echo "OK - File '" . $details['name'] . "' successfully downloaded to the local directory\n";
    }

    /**
     * Checks whether the given user email address is assigned to the given
     * issue ID.
     *
     * @param   RemoteApi $client The connection resource
     * @param   array $auth Array of authentication information (email, password)
     * @param   int $issue_id The issue ID
     */
    public function checkIssueAssignment($client, $auth, $issue_id)
    {
        // check if the confirmation message was already displayed
        if (isset(self::$_displayed_confirmation) && !self::$_displayed_confirmation) {
            // check if the current user is allowed to change the given issue
            $may_change_issue = $client->mayChangeIssue($auth[0], $auth[1], $issue_id);

            // if not, show confirmation message
            if ($may_change_issue != 'yes') {
                echo "WARNING: You are not currently assigned to issue #$issue_id.\n";
                self::promptConfirmation($client, $auth, $issue_id, false);
            }
        }
    }

    /**
     * Checks whether the given user email address is allowed to work with the
     * given issue ID.
     *
     * @param   RemoteApi $client The connection resource
     * @param   array $auth Array of authentication information (email, password)
     * @param   int $issue_id The issue ID
     * @return array The issue details, if the user is allowed to work on it
     */
    public function checkIssuePermissions($client, $auth, $issue_id)
    {
        $projects = self::getUserAssignedProjects($client, $auth);
        $details = $client->getIssueDetails($auth[0], $auth[1], $issue_id);
        $details['iss_prj_id'] = (int) $details['iss_prj_id'];

        // check if the issue the user is trying to change is inside a project viewable to him
        $found = 0;
        foreach ($projects as $i => $project) {
            if ($details['iss_prj_id'] == $project['id']) {
                $found = 1;
                break;
            }
        }
        if (!$found) {
            self::quit("The assigned project for issue #$issue_id doesn't match any in the list of projects assigned to you");
        }

        return $details;
    }

    /**
     * Method used to assign an issue to the current user.
     *
     * @param   RemoteApi $client The connection resource
     * @param   array $auth Array of authentication information (email, password)
     * @param   int $issue_id The issue ID
     * @param   string $developer The email address of the assignee
     */
    public static function assignIssue($client, $auth, $issue_id, $developer)
    {
        // check if the given email address is indeed an email
        if (!strstr($developer, '@')) {
            self::quit('The third argument for this command needs to be a valid email address');
        }
        $details = self::checkIssuePermissions($client, $auth, $issue_id);

        $result = $client->assignIssue($auth[0], $auth[1], $issue_id, $details['iss_prj_id'], $developer);
        if ($result == 'OK') {
            echo "OK - Issue #$issue_id successfully assigned to '$developer'\n";
        } else {
            die("Not OK\n");
        }
    }

    /**
     * Method used to assign an issue to the current user and set status to 'assigned'.
     * If issue is already assigned to someone else, this will fail.
     *
     * @param   RemoteApi $client The connection resource
     * @param   array $auth Array of authentication information (email, password)
     * @param   int $issue_id The issue ID
     */
    public static function takeIssue($client, $auth, $issue_id)
    {
        $details = self::checkIssuePermissions($client, $auth, $issue_id);

        $result = $client->takeIssue($auth[0], $auth[1], $issue_id, (int) $details['iss_prj_id']);
        if ($result == 'OK') {
            echo "OK - Issue #$issue_id successfully taken.\n";
        } else {
            die("Not OK\n");
        }
    }

    /**
     * Method used to add an authorized replier
     *
     * @param   RemoteApi $client The connection resource
     * @param   array $auth Array of authentication information (email, password)
     * @param   int $issue_id The issue ID
     * @param   string $new_replier The email address of the assignee
     */
    public static function addAuthorizedReplier($client, $auth, $issue_id, $new_replier)
    {
        // check if the given email address is indeed an email
        if (!strstr($new_replier, '@')) {
            self::quit('The third argument for this command needs to be a valid email address');
        }
        $details = self::checkIssuePermissions($client, $auth, $issue_id);

        $result = $client->addAuthorizedReplier($auth[0], $auth[1], $issue_id, $details['iss_prj_id'], $new_replier);
        if ($result == 'OK') {
            echo "OK - '$new_replier' successfully added as an authorized replier to issue #$issue_id\n";
        } else {
            die("Not OK\n");
        }
    }

    /**
     * Method used to change the status of an issue.
     *
     * @param   RemoteApi $client The connection resource
     * @param   array $auth Array of authentication information (email, password)
     * @param   int $issue_id The issue ID
     * @param   string $new_status The new status title
     */
    public static function setIssueStatus(&$client, $auth, $issue_id, $new_status)
    {
        $details = self::checkIssuePermissions($client, $auth, $issue_id);
        self::checkIssueAssignment($client, $auth, $issue_id);

        // check if the issue already is set to the new status
        if ((strtolower($details['sta_title']) == strtolower($new_status)) ||
                (strtolower($details['sta_abbreviation']) == strtolower($new_status))) {
            self::quit("Issue #$issue_id is already set to status '" . $details['sta_title'] . "'");
        }

        // check if the given status is a valid option
        $statuses = $client->getAbbreviationAssocList($auth[0], $auth[1], (int) $details['iss_prj_id'], false);

        $titles = Misc::lowercase(array_values($statuses));
        $abbreviations = Misc::lowercase(array_keys($statuses));
        if ((!in_array(strtolower($new_status), $titles)) &&
                (!in_array(strtolower($new_status), $abbreviations))) {
            self::quit("Status '$new_status' could not be matched against the list of available statuses");
        }

        // if the user is passing an abbreviation, use the real title instead
        if (in_array(strtolower($new_status), $abbreviations)) {
            $index = array_search(strtolower($new_status), $abbreviations);
            $new_status = $titles[$index];
        }

        $result = $client->setIssueStatus($auth[0], $auth[1], $issue_id, $new_status);
        if ($result == 'OK') {
            echo "OK - Status successfully changed to '$new_status' on issue #$issue_id\n";
        } else {
            die("Not OK\n");
        }
    }

    /**
     * Method used to add a time tracking entry to an existing issue.
     *
     * @param   RemoteApi $client The connection resource
     * @param   array $auth Array of authentication information (email, password)
     * @param   int $issue_id The issue ID
     * @param   string $time_spent The time spent in minutes
     */
    public static function addTimeEntry($client, $auth, $issue_id, $time_spent)
    {
        self::checkIssuePermissions($client, $auth, $issue_id);
        self::checkIssueAssignment($client, $auth, $issue_id);

        // list the time tracking categories
        $cats = $client->getTimeTrackingCategories($auth[0], $auth[1], $issue_id);

        $prompt = "Which time tracking category would you like to associate with this time entry?\n";
        foreach ($cats as $id => $title) {
            $prompt .= sprintf(" [%s] => %s\n", $id, $title);
        }
        $prompt .= 'Please enter the number of the time tracking category';
        $cat_id = CLI_Misc::prompt($prompt, false);
        if (!in_array($cat_id, array_keys($cats))) {
            self::quit("The selected time tracking category number didn't match any existing category");
        }

        $prompt = 'Please enter a quick summary of what you worked on';
        $summary = CLI_Misc::prompt($prompt, false);

        $result = $client->recordTimeWorked($auth[0], $auth[1], $issue_id, (int) $cat_id, $summary, $time_spent);
        if ($result == 'OK') {
            echo "OK - Added time tracking entry to issue #$issue_id\n";
        } else {
            die("Not OK\n");
        }
    }

    /**
     * Method used to print the current details for a given issue.
     *
     * @param   RemoteApi $client The connection resource
     * @param   array $auth Array of authentication information (email, password)
     * @param   int $issue_id The issue ID
     */
    public static function printIssueDetails($client, $auth, $issue_id, $full = false)
    {
        $details = self::checkIssuePermissions($client, $auth, $issue_id);

        $msg = '';
        if (!empty($details['quarantine']['iqu_status'])) {
            $msg .= '        WARNING: Issue is currently quarantined!';
            if (!empty($details['quarantine']['iqu_expiration'])) {
                $msg .= ' Quarantine expires in ' . $details['quarantine']['time_till_expiration'];
            }
            $msg .= "\n";
        }
        $msg .= "        Issue #: $issue_id
        Summary: " . $details['iss_summary'] . '
         Status: ' . $details['sta_title'] . '
     Assignment: ' . $details['assignments'] . '
 Auth. Repliers: ' . @implode(', ', $details['authorized_names']) . '
       Reporter: ' . $details['reporter'];
        if (@isset($details['customer'])) {
            $msg .= '
       Customer: ' . @$details['customer']['name'] . '
  Support Level: ' . @$details['contract']['support_level'] . '
Support Options: ' . @$details['contract']['options_display'] . '
          Phone: ' . $details['iss_contact_phone'] . '
       Timezone: ' . $details['iss_contact_timezone'] . '
Account Manager: ' . @$details['customer']['account_manager_name'];
        }
        $iss_updated_date = Date_Helper::getFormattedDate($details['iss_updated_date']);
        $iss_last_response_date = Date_Helper::getFormattedDate($details['iss_last_response_date']);
        $msg .= '
  Last Response: ' . $iss_last_response_date . '
   Last Updated: ' . $iss_updated_date . "\n";
        echo $msg;

        if ($full) {
            self::printIssueCustomFields($client, $auth, $issue_id, $details);
        }
    }

    /**
     * Method used to print the custom fields for a given issue.
     *
     * @param   RemoteApi $client The connection resource
     * @param   array $auth Array of authentication information (email, password)
     * @param   int $issue_id The issue ID
     * @param   array $details
     */
    public static function printIssueCustomFields($client, $auth, $issue_id, $details = null)
    {
        if (!$details) {
            $details = self::checkIssuePermissions($client, $auth, $issue_id);
        }
        $msg = '';
        // start custom fields management
        if (!empty($details['custom_fields'])) {
            foreach ($details['custom_fields'] as $custom_field) {
                $msg .= str_pad($custom_field['fld_title'], 15, ' ', STR_PAD_LEFT) . ': ' .
              $custom_field['value'] . "\n";
            }
        }

        echo $msg;
    }

    /**
     * Method used to print the list of open issues.
     *
     * @param   RemoteApi $client The connection resource
     * @param   array $auth Array of authentication information (email, password)
     * @param   string $show_all_issues Whether to show all open issues or just the ones assigned to the current user
     * @param   string $status The status that should be used to restrict the results
     */
    public static function printOpenIssues($client, $auth, $show_all_issues, $status)
    {
        $project_id = self::promptProjectSelection($client, $auth);
        // check the status option
        // check if the given status is a valid option
        if (!empty($status)) {
            $statuses = $client->getAbbreviationAssocList($auth[0], $auth[1], $project_id, true);

            $titles = Misc::lowercase(array_values($statuses));
            $abbreviations = Misc::lowercase(array_keys($statuses));
            if ((!in_array(strtolower($status), $titles)) &&
                    (!in_array(strtolower($status), $abbreviations))) {
                self::quit("Status '$status' could not be matched against the list of available statuses");
            }
            // if the user is passing an abbreviation, use the real title instead
            if (in_array(strtolower($status), $abbreviations)) {
                $status = $statuses[strtoupper($status)];
            }
        }

        $issues = $client->getOpenIssues($auth[0], $auth[1], $project_id, $show_all_issues, $status);

        if (!empty($status)) {
            echo "The following issues are set to status '$status':\n";
        } else {
            echo "The following issues are still open:\n";
        }

        foreach ($issues as $issue) {
            echo '- #' . $issue['issue_id'] . ' - ' . $issue['summary'] . ' (' . $issue['status'] . ')';
            if (!empty($issue['assigned_users'])) {
                echo ' - (' . $issue['assigned_users'] . ')';
            } else {
                echo ' - (unassigned)';
            }
            echo "\n";
        }
    }

    /**
     * Method used to get the list of projects assigned to a given email address.
     *
     * @param   RemoteApi $client The connection resource
     * @param   array $auth Array of authentication information (email, password)
     * @param   bool $only_customer_projects Whether to only include projects with customer integration or not
     * @return  array The list of projects
     */
    public function getUserAssignedProjects($client, $auth, $only_customer_projects = false)
    {
        $result = $client->getUserAssignedProjects($auth[0], $auth[1], $only_customer_projects);

        return $result;
    }

    /**
     * Method used to prompt the current user to select a project.
     *
     * @param   RemoteApi $client The connection resource
     * @param   array $auth Array of authentication information (email, password)
     * @param   bool $only_customer_projects Whether to only include projects with customer integration or not
     * @return  int The project ID
     */
    public function promptProjectSelection($client, $auth, $only_customer_projects = false)
    {
        // list the projects that this user is assigned to
        $projects = self::getUserAssignedProjects($client, $auth, $only_customer_projects);

        if (count($projects) > 1) {
            // need to ask which project this person is asking about
            $prompt = "For which project do you want this action to apply to?\n";
            $nprojects = count($projects);
            for ($i = 0; $i < $nprojects; $i++) {
                $prompt .= sprintf(" [%s] => %s\n", $projects[$i]['id'], $projects[$i]['title']);
            }
            $prompt .= 'Please enter the number of the project';
            $project_id = CLI_Misc::prompt($prompt, false);
            $found = 0;
            for ($i = 0; $i < $nprojects; $i++) {
                if ($project_id == $projects[$i]['id']) {
                    $found = 1;
                    break;
                }
            }
            if (!$found) {
                self::quit("Entered project number doesn't match any in the list of projects assigned to you");
            }
        } else {
            $project_id = $projects[0]['id'];
        }

        return (int) $project_id;
    }

    /**
     * Method used to print the available statuses associated with the
     * currently selected project.
     *
     * @param   RemoteApi $client The connection resource
     * @param   array $auth Array of authentication information (email, password)
     */
    public static function printStatusList($client, $auth)
    {
        $project_id = self::promptProjectSelection($client, $auth);
        $items = $client->getAbbreviationAssocList($auth[0], $auth[1], $project_id, true);

        echo "Available Statuses:\n";
        foreach ($items as $abbreviation => $title) {
            echo "$abbreviation => $title\n";
        }
    }

    /**
     * Method used to print the list of developers.
     *
     * @param   RemoteApi $client The connection resource
     * @param   array $auth Array of authentication information (email, password)
     */
    public static function printDeveloperList($client, $auth)
    {
        $project_id = self::promptProjectSelection($client, $auth);
        $developers = $client->getDeveloperList($auth[0], $auth[1], $project_id);

        echo "Available Developers:\n";
        foreach ($developers as $name => $email) {
            echo "-> $name - $email\n";
        }
    }

    /**
     * Method used to list emails for a given issue.
     *
     * @param   RemoteApi $client The connection resource
     * @param   array $auth Array of authentication information (email, password)
     * @param   int $issue_id The issue ID
     */
    public static function listEmails(&$client, $auth, $issue_id)
    {
        self::checkIssuePermissions($client, $auth, $issue_id);

        $emails = $client->getEmailListing($auth[0], $auth[1], $issue_id);

        if (!is_array($emails) || count($emails) < 1) {
            echo "No emails for this issue\n";
            exit;
        }

        $format = [
            'id' => [
                'width' => 3,
                'title' => 'ID',
            ],
            'sup_date' => [
                'width' => 30,
                'title' => 'Date',
            ],
            'sup_from' => [
                'width' => 24,
                'title' => 'From',
            ],
            'sup_cc' => [
                'width' => 24,
                'title' => 'CC',
            ],
            'sup_subject' => [
                'width' => 30,
                'title' => 'Subject',
            ],
        ];

        self::printTable($format, $emails);
    }

    /**
     * Method to show the contents of an email.
     *
     * @param   RemoteApi $client The connection resource
     * @param   array $auth Array of authentication information (email, password)
     * @param   int $issue_id The issue ID
     * @param   int $email_id The sequential id of the email to view
     * @param   bool $display_full if the full email should be displayed
     */
    public static function printEmail($client, $auth, $issue_id, $email_id, $display_full)
    {
        self::checkIssuePermissions($client, $auth, $issue_id);

        $email = $client->getEmail($auth[0], $auth[1], $issue_id, (int) $email_id);

        if ($display_full) {
            echo $email['seb_full_email'];
        } else {
            $sup_date = Date_Helper::getFormattedDate($email['sup_date']);
            echo sprintf("%15s: %s\n", 'Date', $sup_date);
            echo sprintf("%15s: %s\n", 'From', $email['sup_from']);
            echo sprintf("%15s: %s\n", 'To', $email['sup_to']);
            echo sprintf("%15s: %s\n", 'CC', $email['sup_cc']);
            echo sprintf("%15s: %s\n", 'Attachments?', (($email['sup_has_attachment'] == 1) ? 'yes' : 'no'));
            echo sprintf("%15s: %s\n", 'Subject', $email['sup_subject']);
            echo "------------------------------------------------------------------------\n";
            echo $email['message'];
            if (substr($email['message'], -1) != "\n") {
                echo "\n";
            }
        }
    }

    /**
     * Method used to list notes for a given issue.
     *
     * @param   RemoteApi $client The connection resource
     * @param   array $auth Array of authentication information (email, password)
     * @param   int $issue_id The issue ID
     */
    public static function listNotes($client, $auth, $issue_id)
    {
        self::checkIssuePermissions($client, $auth, $issue_id);

        $notes = $client->getNoteListing($auth[0], $auth[1], $issue_id);

        foreach ($notes as $i => &$note) {
            if ($note['has_blocked_message'] == 1) {
                $note['not_title'] = '(BLOCKED) ' . $note['not_title'];
            }
            $note['id'] = ($i + 1);
        }

        if (count($notes) < 1) {
            echo "No notes for this issue\n";
            exit;
        }

        $format = [
            'id' => [
                'width' => 3,
                'title' => 'ID',
            ],
            'usr_full_name' => [
                'width' => 24,
                'title' => 'User',
            ],
            'not_title' => [
                'width' => 50,
                'title' => 'Title',
            ],
            'not_created_date' => [
                'width' => 30,
                'title' => 'Date',
            ],
        ];

        self::printTable($format, $notes);
    }

    /**
     * Method to show the contents of a note.
     *
     * @param   RemoteApi $client The connection resource
     * @param   array $auth Array of authentication information (email, password)
     * @param   int $issue_id The issue ID
     * @param   int $note_id The sequential id of the note to view
     */
    public static function printNote($client, $auth, $issue_id, $note_id)
    {
        self::checkIssuePermissions($client, $auth, $issue_id);

        $note = self::getNote($client, $auth, $issue_id, $note_id);
        $not_created_date = Date_Helper::getFormattedDate($note['not_created_date']);
        echo sprintf("%15s: %s\n", 'Date', $not_created_date);
        echo sprintf("%15s: %s\n", 'From', $note['not_from']);
        echo sprintf("%15s: %s\n", 'Title', $note['not_title']);
        echo "------------------------------------------------------------------------\n";
        echo $note['not_note'];
    }

    /**
     * Returns the contents of a note via XML-RPC.
     *
     * @param   RemoteApi $client The connection resource
     * @param   array $auth Array of authentication information (email, password)
     * @param   int $issue_id The issue ID
     * @param   int $note_id The sequential id of the note to view
     * @return  array an array containing note details
     */
    public function getNote($client, $auth, $issue_id, $note_id)
    {
        $note = $client->getNote($auth[0], $auth[1], $issue_id, (int) $note_id);

        return $note;
    }

    /**
     * Converts a note into a draft or an email.
     *
     * @param   RemoteApi $client The connection source
     * @param   array $auth Array of authentication information (email, password)
     * @param   int $issue_id The issue ID
     * @param   int $note_id The sequential id of the note to view
     * @param   string $target what this note should be converted too, a draft or an email
     * @param   bool $authorize_sender if the sender should be added to the authorized repliers list
     */
    public static function convertNote($client, $auth, $issue_id, $note_id, $target, $authorize_sender)
    {
        self::checkIssuePermissions($client, $auth, $issue_id);
        self::checkIssueAssignment($client, $auth, $issue_id);

        $note_details = self::getNote($client, $auth, $issue_id, (int) $note_id);
        if (count($note_details) < 2) {
            self::quit("Note #$note_id does not exist for issue #$issue_id");
        } elseif ($note_details['has_blocked_message'] != 1) {
            self::quit("Note #$note_id does not have a blocked message attached so cannot be converted");
        }

        $message = $client->convertNote($auth[0], $auth[1], $issue_id, (int) $note_details['not_id'], $target, $authorize_sender);
        if ($message == 'OK') {
            echo "OK - Note successfully converted to $target\n";
        }
    }

    /**
     * Fetches the weekly report for the current developer for the specified week.
     *
     * @param   RemoteApi $client The connection resource
     * @param   array $auth Array of authentication information (email, password)
     * @param   int $week The week for the report. If start and end date are set, this is ignored.
     * @param   string $start_date The start date of the report. (optional)
     * @param   string $end_date The end_date of the report. (optional)
     * @param   bool $separate_closed if closed issues should be separated from other issues
     */
    public static function getWeeklyReport($client, $auth, $week, $start_date = '', $end_date = '', $separate_closed = false)
    {
        $prj_id = self::promptProjectSelection($client, $auth);
        $ret = $client->getWeeklyReport($auth[0], $auth[1], (int) $week, $start_date, $end_date, $separate_closed, $prj_id);
        echo $ret;
    }

    /**
     * Clocks a user in/out of the system.
     *
     * @param   RemoteApi $client The connection resource
     * @param   array $auth Array of authentication information (email, password)
     * @param   string $action if the user is clocking in or out
     */
    public static function timeClock($client, $auth, $action)
    {
        $result = $client->timeClock($auth[0], $auth[1], $action);
        echo $result;
    }

    /**
     * Lists drafts associated with an issue.
     *
     * @param   RemoteApi $client The connection resource
     * @param   array $auth Array of authentication information (email, password)
     * @param   int $issue_id The issue ID
     */
    public static function listDrafts($client, $auth, $issue_id)
    {
        self::checkIssuePermissions($client, $auth, $issue_id);

        $drafts = $client->getDraftListing($auth[0], $auth[1], $issue_id);

        if (count($drafts) < 1) {
            echo "No drafts for this issue\n";
            exit;
        }

        foreach ($drafts as $i => &$draft) {
            $draft['id'] = ($i + 1);
        }

        $format = [
            'id' => [
                'width' => 3,
                'title' => 'ID',
            ],
            'from' => [
                'width' => 24,
                'title' => 'From',
            ],
            'to' => [
                'width' => 24,
                'title' => 'To',
            ],
            'emd_subject' => [
                'width' => 30,
                'title' => 'Title',
            ],
            'emd_updated_date' => [
                'width' => 30,
                'title' => 'Date',
            ],
        ];

        self::printTable($format, $drafts);
    }

    /**
     * Method to show the contents of a draft.
     *
     * @param   RemoteApi $client The connection resource
     * @param   array $auth Array of authentication information (email, password)
     * @param   int $issue_id The issue ID
     * @param   int $draft_id The sequential id of the draft to view
     */
    public static function printDraft($client, $auth, $issue_id, $draft_id)
    {
        self::checkIssuePermissions($client, $auth, $issue_id);

        $draft = self::getDraft($client, $auth, $issue_id, $draft_id);
        $emd_updated_date = Date_Helper::getFormattedDate($draft['emd_updated_date']);
        echo sprintf("%15s: %s\n", 'Date', $emd_updated_date);
        echo sprintf("%15s: %s\n", 'From', $draft['from']);
        echo sprintf("%15s: %s\n", 'To', $draft['to']);
        if (!empty($draft['cc'])) {
            echo sprintf("%15s: %s\n", 'Cc', $draft['cc']);
        }
        echo sprintf("%15s: %s\n", 'Title', $draft['emd_subject']);
        echo "------------------------------------------------------------------------\n";
        echo $draft['emd_body'];
    }

    /**
     * Returns the contents of a draft via XML-RPC.
     *
     * @param   RemoteApi $client The connection resource
     * @param   array $auth Array of authentication information (email, password)
     * @param   int $issue_id The issue ID
     * @param   int $draft_id The sequential id of the draft to view
     * @return  array an array containing draft details
     */
    public function getDraft($client, $auth, $issue_id, $draft_id)
    {
        $draft = $client->getDraft($auth[0], $auth[1], $issue_id, (int) $draft_id);

        return $draft;
    }

    /**
     * Converts a draft to an email and sends it.
     *
     * @param   RemoteApi $client The connection resource
     * @param   array $auth Array of authentication information (email, password)
     * @param   int $issue_id The issue ID
     * @param   int $draft_id The sequential id of the draft to send
     * @return  array an array containing draft details
     */
    public static function sendDraft($client, $auth, $issue_id, $draft_id)
    {
        $result = $client->sendDraft($auth[0], $auth[1], $issue_id, (int) $draft_id);
        echo $result;
    }

    /**
     * Marks an issue as redeemed incident
     *
     * @param   RemoteApi $client The connection resource
     * @param   array $auth Array of authentication information (email, password)
     * @param   int $issue_id The issue ID
     */
    public static function redeemIssue($client, $auth, $issue_id)
    {
        self::checkIssuePermissions($client, $auth, $issue_id);
        self::checkIssueAssignment($client, $auth, $issue_id);

        $types = self::promptIncidentTypes($client, $auth, $issue_id);

        $result = $client->redeemIssue($auth[0], $auth[1], $issue_id, $types);
        echo "OK - Issue #$issue_id successfully marked as redeemed incident.\n";
    }

    /**
     * Un-marks an issue as redeemed incident
     *
     * @param   RemoteApi $client The connection resource
     * @param   array $auth Array of authentication information (email, password)
     * @param   int $issue_id The issue ID
     */
    public static function unredeemIssue($client, $auth, $issue_id)
    {
        self::checkIssuePermissions($client, $auth, $issue_id);
        self::checkIssueAssignment($client, $auth, $issue_id);

        $types = self::promptIncidentTypes($client, $auth, $issue_id, true);

        $result = $client->unredeemIssue($auth[0], $auth[1], $issue_id, $types);
        echo "OK - Issue #$issue_id successfully marked as unredeemed incident.\n";
    }

    /**
     * Returns the list of incident types available.
     *
     * @param   RemoteApi $client The connection resource
     * @param   array $auth Array of authentication information (email, password)
     * @param   int $issue_id The issue ID
     * @param   bool $redeemed_only if this should only show items that have been redeemed
     * @return array|string
     */
    public function promptIncidentTypes($client, $auth, $issue_id, $redeemed_only = false)
    {
        $types = $client->getIncidentTypes($auth[0], $auth[1], $issue_id, $redeemed_only);

        if (count($types) < 1) {
            if ($redeemed_only) {
                self::quit('No incident types have been redeemed for this issue');
            } else {
                self::quit('All incident types have already been redeemed for this issue');
            }
        }

        $prompt = 'Please enter a comma separated list of incident types to ';
        if ($redeemed_only) {
            $prompt .= 'un';
        }
        $prompt .= "redeem for this issue.\n";
        foreach ($types as $id => $data) {
            $prompt .= sprintf(" [%s] => %s (Total: %s; Left: %s)\n", $id, $data['title'], $data['total'], ($data['total'] - $data['redeemed']));
        }
        $requested_types = CLI_Misc::prompt($prompt, false);
        $requested_types = explode(',', $requested_types);
        if (count($requested_types) < 1) {
            self::quit('Please enter a comma separated list of issue types');
        }

        $type_keys = array_keys($types);
        foreach ($requested_types as $type_id) {
            if (!in_array($type_id, $type_keys)) {
                self::quit("Input '$type_id' is not a valid incident type");
            }
        }

        return $requested_types;
    }

    /**
     * Method to print data in a formatted table, according to the $format array.
     *
     * @param   array $format An array containing how to format the data
     * @param   array $data An array of data to be printed
     */
    public function printTable($format, $data)
    {
        // loop through the fields, printing out the header row
        $firstRow = '';
        $secondRow = '';
        foreach ($format as $column) {
            $firstRow .= sprintf('%-' . $column['width'] . 's', $column['title']) . ' ';
            $secondRow .= sprintf("%-'-" . $column['width'] . 's', '') . ' ';
        }
        echo $firstRow . "\n" . $secondRow . "\n";
        // print out data
        $ndata = count($data);
        for ($i = 0; $i < $ndata; $i++) {
            foreach ($format as $key => $column) {
                echo sprintf('%-' . $column['width'] . 's', substr($data[$i][$key], 0, $column['width'])) . ' ';
            }
            echo "\n";
        }
    }

    /**
     * Method used to print a confirmation prompt with the current details
     * of the given issue. The $command parameter can be used to determine what type of
     * confirmation to show to the user.
     *
     * @param   RemoteApi $client The connection resource
     * @param   array $auth Array of authentication information (email, password)
     * @param   int $issue_id The issue ID
     * @param   string $args The arguments passed to this script
     */
    public static function promptConfirmation($client, $auth, $issue_id, $args)
    {
        // this is needed to prevent multiple confirmations from being shown to the user
        self::$_displayed_confirmation = true;

        // get summary, customer status and assignment of issue, then show confirmation prompt to user
        $details = $client->getSimpleIssueDetails($auth[0], $auth[1], $issue_id);

        switch ($args[2]) {
            case 'convert-note':
            case 'cn':
                $note_details = self::getNote($client, $auth, $issue_id, $args[3]);
                $not_created_date = Date_Helper::getFormattedDate($note_details['not_created_date']);
                $msg = "These are the current details for issue #$issue_id, note #" . $args[3] . ":\n" .
                        '   Date: ' . $not_created_date . "\n" .
                        '   From: ' . $note_details['not_from'] . "\n" .
                        '  Title: ' . $note_details['not_title'] . "\n" .
                        'Are you sure you want to convert this note into a ' . $args[4] . '?';
                break;
            default:
                $msg = "These are the current details for issue #$issue_id:\n" .
                        '         Summary: ' . $details['summary'] . "\n";
                if (@!empty($details['customer'])) {
                    $msg .= '        Customer: ' . $details['customer'] . "\n";
                }
                $msg .= '          Status: ' . $details['status'] . "\n" .
                        '      Assignment: ' . $details['assignments'] . "\n" .
                        '  Auth. Repliers: ' . $details['authorized_names'] . "\n" .
                        'Are you sure you want to change this issue?';
        }

        $ret = CLI_Misc::prompt($msg, 'y');
        if (strtolower($ret) != 'y') {
            exit;
        }
    }

    /**
     * Method used to check the authentication of the current user.
     *
     * @param   RemoteApi $client The connection resource
     * @param   string $email The email address of the current user
     * @param   string $password The password of the current user
     */
    public static function checkAuthentication($client, $email, $password)
    {
        $is_valid = $client->isValidLogin($email, $password);
        if (!$is_valid) {
            self::quit('Login information could not be authenticated');
        }
    }

    /**
     * Logs the current command
     *
     * @param   RemoteApi $client The connection resource
     * @param   array $auth Array of authentication information (email, password)
     * @param   string $command the command used to run this script
     */
    public static function log($client, $auth, $command)
    {
        try {
            $client->logCommand($auth[0], $auth[1], $command);
        } catch (Eventum_RPC_Exception $e) {
            self::quit($e->getMessage());
        }
    }

    /**
     * Method used to check whether the current execution needs to have a
     * confirmation message shown before performing the requested action or not.
     *
     * @return  bool
     */
    public static function isSafeExecution()
    {
        global $argv, $argc;
        if ($argv[count($argv) - 1] == '--safe') {
            array_pop($argv);

            return true;
        }

        return false;
    }

    /**
     * Method used to print a usage statement for the command line interface.
     *
     * @param   string $script The current script name
     */
    public static function usage($script)
    {
        $usage = [];
        $usage[] = [
            'command' => '<ticket_number> [--full]',
            'help' => 'View general details of an existing issue. --full displays also custom fields.',
        ];
        $usage[] = [
            'command' => ['<ticket_number> custom-fields', '<ticket_number> cf'],
            'help' => 'List custom fields associated with the given issue.',
        ];
        $usage[] = [
            'command' => '<ticket_number> assign <developer_email> [--safe]',
            'help' => 'Assign an issue to another developer.',
        ];
        $usage[] = [
            'command' => '<ticket_number> take [--safe]',
            'help' => "Assign an issue to yourself and change status to 'Assigned'.",
        ];
        $usage[] = [
            'command' => ['<ticket_number> add-replier <user_email> [--safe]', '<ticket_number> ar <user_email> [--safe]'],
            'help' => 'Adds the specified user to the list of authorized repliers.',
        ];
        $usage[] = [
            'command' => '<ticket_number> set-status <status> [--safe]',
            'help' => "Sets the status of an issue to the desired value. If you are not sure
     about the available statuses, use command 'list-status' described below.",
        ];
        $usage[] = [
            'command' => '<ticket_number> add-time <time_worked> [--safe]',
            'help' => 'Records time worked to the time tracking tool of the given issue.',
        ];
        $usage[] = [
            'command' => '<ticket_number> close [--safe]',
            'help' => 'Marks an issue as closed.',
        ];
        $usage[] = [
            'command' => ['<ticket_number> list-files', '<ticket_number> lf'],
            'help' => 'List available attachments associated with the given issue.',
        ];
        $usage[] = [
            'command' => ['<ticket_number> get-file <file_number>', '<ticket_number> gf <file_number>'],
            'help' => 'Download a specific file from the given issue.',
        ];
        $usage[] = [
            'command' => ['<ticket_number> list-emails', '<ticket_number> le'],
            'help' => 'Lists emails from the given issue.',
        ];
        $usage[] = [
            'command' => ['<ticket_number> get-email <email_number> [--full]', '<ticket_number> ge <email_number> [--full]'],
            'help' => 'Displays a specific email for the issue. If the optional --full parameter
     is specified, the full email including headers and attachments will be
     displayed.',
        ];
        $usage[] = [
            'command' => ['<ticket_number> list-notes', '<ticket_number> ln'],
            'help' => 'Lists notes from the given issue.',
        ];
        $usage[] = [
            'command' => ['<ticket_number> get-note <note_number> [--full]', '<ticket_number> gn <note_number>'],
            'help' => 'Displays a specific note for the issue.',
        ];
        $usage[] = [
            'command' => ['<ticket_number> convert-note <note_number> draft|email [authorize] [--safe]', '<ticket_number> cn <note_number> draft|email [authorize] [--safe]'],
            'help' => "Converts the specified note to a draft or an email.
    Use optional argument 'authorize' to add sender to authorized repliers list.",
        ];
        $usage[] = [
            'command' => ['<ticket_number> list-drafts', '<ticket_number> ld'],
            'help' => 'Lists drafts from the given issue.',
        ];
        $usage[] = [
            'command' => ['<ticket_number> get-draft <draft_number>', '<ticket_number> gd <draft_number>'],
            'help' => 'Displays a specific draft for the issue.',
        ];
        $usage[] = [
            'command' => ['<ticket_number> send-draft <draft_number>', '<ticket_number> sd <draft_number>'],
            'help' => 'Converts a draft to an email and sends it out.',
        ];
        $usage[] = [
            'command' => ['<ticket_number> redeem'],
            'help' => 'Marks an issue as redeemed incident.',
        ];
        $usage[] = [
            'command' => ['<ticket_number> unredeem'],
            'help' => 'Un-marks an issue as redeemed incident.',
        ];
        $usage[] = [
            'command' => 'developers',
            'help' => "List all available developers' email addresses.",
        ];
        $usage[] = [
            'command' => 'open-issues [<status>] [my]',
            'help' => "List all issues that are not set to a status with a 'closed' context. Use
     optional argument 'my' if you just wish to see issues assigned to you.",
        ];
        $usage[] = [
            'command' => 'list-status',
            'help' => 'List all available statuses in the system.',
        ];
        $usage[] = [
            'command' => 'customer email|support|customer <value>',
            'help' => "Looks up a customer's record information.",
        ];
        $usage[] = [
            'command' => ['weekly-report ([<week>] [--separate-closed])|([<start>] [<end>] [--separate-closed])', 'wr ([<week>])|([<start>] [<end>] [--separate-closed])'],
            'help' => "Fetches the weekly report. Week is specified as an integer with 0 representing
     the current week, -1 the previous week and so on. If the week is omitted it defaults
     to the current week. Alternately, a date range can be set. Dates should be in the format 'YYYY-MM-DD'.",
        ];
        $usage[] = [
            'command' => 'clock [in|out]',
            'help' => 'Clocks you in or out of the system. When clocked out, no reminders will be sent to your account.
     If the in|out parameter is left off, your current status is displayed.',
        ];
        $script = basename($script);
        $usage_text = '';
        $explanation = '';
        foreach ($usage as $command_num => $this_command) {
            $item_num = sprintf('%2d.) ', ($command_num + 1));
            $usage_text .= $item_num . "$script ";
            if (is_array($this_command['command'])) {
                $ncommands = count($this_command['command']);
                for ($i = 0; $i < $ncommands; $i++) {
                    if ($i != 0) {
                        $usage_text .= "     $script ";
                    }
                    $usage_text .= $this_command['command'][$i] . "\n";
                }
            } else {
                $usage_text .= $this_command['command'] . "\n";
            }
            $explanation .= $item_num . $this_command['help'] . "\n\n";
        }
        echo "
General Usage:
$usage_text

Explanations:
$explanation
";
        exit;
    }

    /**
     * Method used to print a message to standard output and halt processing.
     *
     * @param   string $msg The message that needs to be printed
     */
    public static function quit($msg)
    {
        die("Error - $msg. Run script with --help for usage information.\n");
    }
}
