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

require_once APP_INC_PATH . '/class.misc.php';
require_once 'PEAR.php';
require_once 'XML/RPC.php';

$_displayed_confirmation = false;

class Command_Line
{
    /**
     * Prompts the user for a resolution option, and returns the ID of the
     * selected one.
     *
     * @access  public
     * @param   resource $rpc_conn The connection resource
     * @return  integer The selected resolution id
     */
    function promptResolutionSelection(&$rpc_conn)
    {
        $msg = new XML_RPC_Message("getResolutionAssocList");
        $result = $rpc_conn->send($msg);
        if ($result->faultCode()) {
            self::quit($result->faultString());
        }
        $list = XML_RPC_decode($result->value());
        if (count($list) > 1) {
            // need to ask which status this person wants to use
            $prompt = "Which resolution do you want to use in this action?\n";
            foreach ($list as $key => $value) {
                $prompt .= sprintf(" [%s] => %s\n", $key, $value);
            }
            $prompt .= "Please enter the resolution";
            $resolution_id = Misc::prompt($prompt, false);
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
     * @access  public
     * @param   resource $rpc_conn The connection resource
     * @param   array $auth Array of authentication information (email, password)
     * @param   integer $prj_id The project ID
     * @return  string The selected status title
     */
    function promptStatusSelection(&$rpc_conn, $auth, $prj_id)
    {
        $msg = new XML_RPC_Message("getClosedAbbreviationAssocList", array(new XML_RPC_Value($auth[0], 'string'), new XML_RPC_Value($auth[1], 'string'),
                        new XML_RPC_Value($prj_id, 'int')));
        $result = $rpc_conn->send($msg);
        if ($result->faultCode()) {
            self::quit($result->faultString());
        }
        $list = XML_RPC_decode($result->value());
        if (count($list) > 1) {
            // need to ask which status this person wants to use
            $prompt = "Which status do you want to use in this action?\n";
            foreach ($list as $key => $value) {
                $prompt .= sprintf(" [%s] => %s\n", $key, $value);
            }
            $prompt .= "Please enter the status";
            $status = Misc::prompt($prompt, false);
            $lowercase_keys = array_map('strtolower', array_keys($list));
            $lowercase_values = array_map('strtolower', array_values($list));
            if ((!in_array(strtolower($status), $lowercase_keys)) &&
                    (!in_array(strtolower($status), $lowercase_values)))  {
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
     * @access  public
     * @param   resource $rpc_conn The connection resource
     * @param   array $auth Array of authentication information (email, password)
     * @param   integer $issue_id The issue ID
     */
    function closeIssue(&$rpc_conn, $auth, $issue_id)
    {
        $details = self::checkIssuePermissions($rpc_conn, $auth, $issue_id);
        self::checkIssueAssignment($rpc_conn, $auth, $issue_id);

        // prompt for status selection (accept abbreviations)
        $new_status = self::promptStatusSelection($rpc_conn, $auth, $details['iss_prj_id']);
        // check if the issue already is set to the new status
        if ((strtolower($details['sta_title']) == strtolower($new_status)) ||
                (strtolower($details['sta_abbreviation']) == strtolower($new_status))) {
            self::quit("Issue #$issue_id is already set to status '" . $details['sta_title'] . "'");
        }

        // prompt for status selection (accept abbreviations)
        $resolution_id = self::promptResolutionSelection($rpc_conn);

        // ask whether to send a notification email about this action or not (defaults to yes)
        $msg = "Would you like to send a notification email about this issue being closed? [y/n]";
        $ret = Misc::prompt($msg, false);
        if (strtolower($ret) == 'y') {
            $send_notification = true;
        } else {
            $send_notification = false;
        }

        // prompt for internal note
        $prompt = "Please enter a reason for closing this issue (one line only)";
        $note = Misc::prompt($prompt, false);

        $params = array(
            new XML_RPC_Value($auth[0], 'string'),
            new XML_RPC_Value($auth[1], 'string'),
            new XML_RPC_Value($issue_id, 'int'),
            new XML_RPC_Value($new_status),
            new XML_RPC_Value($resolution_id, 'int'),
            new XML_RPC_Value($send_notification, 'boolean'),
            new XML_RPC_Value($note)
        );
        $msg = new XML_RPC_Message("closeIssue", $params);
        $result = $rpc_conn->send($msg);
        if ($result->faultCode()) {
            self::quit($result->faultString());
        }
        echo "OK - Issue #$issue_id successfully closed.\n";
        if (XML_RPC_decode($result->value()) == 'INCIDENT') {
            echo "WARNING: This customer has incidents. Please redeem incidents by running 'eventum $issue_id redeem'\n";
        }
    }


    /**
     * Looks up customer information given a set of search parameters.
     *
     * @access  public
     * @param   resource $rpc_conn The connection resource
     * @param   array $auth Array of authentication information (email, password)
     * @param   string $field The field in which to search
     * @param   string $value The value to search against
     */
    function lookupCustomer(&$rpc_conn, $auth, $field, $value)
    {
        $project_id = self::promptProjectSelection($rpc_conn, $auth, TRUE);

        $params = array(
            new XML_RPC_Value($auth[0], 'string'),
            new XML_RPC_Value($auth[1], 'string'),
            new XML_RPC_Value($project_id, 'int'),
            new XML_RPC_Value($field),
            new XML_RPC_Value($value)
        );
        $msg = new XML_RPC_Message("lookupCustomer", $params);
        $result = $rpc_conn->send($msg);
        if ($result->faultCode()) {
            self::quit($result->faultString());
        }
        $res = XML_RPC_decode($result->value());
        if (!is_array($res)) {
            echo "ERROR: Sorry, for security reasons you need to wait $res until your next customer lookup.\n";
        } else {
            if (count($res) == 0) {
                echo "Sorry, no customers could be found.\n";
            } else {
                $out = array();
                $out[] = "Customer Lookup Results:\n";
                foreach ($res as $customer) {
                    $out[] = '         Customer: ' . $customer['customer_name'];
                    $out[] = '    Support Level: ' . $customer['support_level'];
                    $out[] = '       Expiration: ' . $customer['expiration_date'];
                    $out[] = '  Contract Status: ' . $customer['contract_status'];
                    // contacts now...
                    if (count($customer['contacts']) > 0) {
                        $out[] = " Allowed Contacts: " . $customer['contacts'][0]['contact_name'] . ' - ' . $customer['contacts'][0]['email'] .
                                (empty($customer['contacts'][0]['phone']) ? '' : (' - ' . $customer['contacts'][0]['phone']));
                        $ncontacts = count($customer['contacts']);
                        for ($i = 1; $i < $ncontacts; $i++) {
                            $out[] = "                   " . $customer['contacts'][$i]['contact_name'] . ' - ' . $customer['contacts'][$i]['email'] .
                                (empty($customer['contacts'][$i]['phone']) ? '' : (' - ' . $customer['contacts'][$i]['phone']));
                        }
                    }
                    $out[] = "\n";
                }
                echo implode("\n", $out);
            }
        }
    }


    /**
     * Method used to parse the eventum command line configuration file
     * and return the appropriate configuration settings.
     *
     * @access  public
     * @return  array The configuration settings
     */
    function getEnvironmentSettings()
    {
        $rcfile = getenv('HOME') . "/.eventumrc";

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
                $value = trim(substr($line, strpos($line, '=')+1));
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
        return array($email, $password, $host, $port, $relative_url);
    }


    /**
     * Prints out a list of attachments associated with the given issue ID.
     *
     * @access  public
     * @param   resource $rpc_conn The connection resource
     * @param   array $auth Array of authentication information (email, password)
     * @param   integer $issue_id The issue ID
     */
    function printFileList(&$rpc_conn, $auth, $issue_id)
    {
        self::checkIssuePermissions($rpc_conn, $auth, $issue_id);

        $msg = new XML_RPC_Message("getFileList", array(new XML_RPC_Value($auth[0], 'string'), new XML_RPC_Value($auth[1], 'string'),
                        new XML_RPC_Value($issue_id, 'int')));
        $result = $rpc_conn->send($msg);
        if ($result->faultCode()) {
            self::quit($result->faultString());
        }
        $list = XML_RPC_decode($result->value());
        $i = 1;
        foreach ($list as $attachment) {
            echo "--------------------------------------------------------------\n";
            echo " Attachment sent by " . $attachment['usr_full_name'] . " on " . $attachment['iat_created_date'] . "\n";
            foreach ($attachment['files'] as $file) {
                echo "  [$i] => " . $file['iaf_filename'] . " (" . $file['iaf_filesize'] . ")\n";
                $i++;
            }
            echo " Description: " . $attachment['iat_description'] . "\n";
        }
    }


    /**
     * Downloads a given attachment file number.
     *
     * @access  public
     * @param   resource $rpc_conn The connection resource
     * @param   array $auth Array of authentication information (email, password)
     * @param   integer $issue_id The issue ID
     * @param   integer $file_number The attachment file number
     */
    function getFile(&$rpc_conn, $auth, $issue_id, $file_number)
    {
        $details = self::checkIssuePermissions($rpc_conn, $auth, $issue_id);

        // check if the provided file number is valid
        $msg = new XML_RPC_Message("getFileList", array(new XML_RPC_Value($auth[0], 'string'), new XML_RPC_Value($auth[1], 'string'),
                    new XML_RPC_Value($issue_id, 'int')));
        $result = $rpc_conn->send($msg);
        if ($result->faultCode()) {
            self::quit($result->faultString());
        }
        $list = XML_RPC_decode($result->value());
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
        $params = array(
            new XML_RPC_Value($auth[0], 'string'),
            new XML_RPC_Value($auth[1], 'string'),
            new XML_RPC_Value($file_id, 'int')
        );
        $msg = new XML_RPC_Message("getFile", $params);
        $result = $rpc_conn->send($msg);
        if ($result->faultCode()) {
            self::quit($result->faultString());
        }
        $details = XML_RPC_decode($result->value());
        $details['iaf_file'] = base64_decode($details['iaf_file']);

        // check if the file already exists
        if (@file_exists($details['iaf_filename'])) {
            $msg = "The requested file ('" . $details['iaf_filename'] . "') already exists in the current directory. Would you like to overwrite this file? [y/n]";
            $ret = Misc::prompt($msg, false);
            if (strtolower($ret) == 'y') {
                @unlink($details['iaf_filename']);
                if (@file_exists($details['iaf_filename'])) {
                    self::quit("No permission to remove the file");
                }
            } else {
                self::quit("Download halted");
            }
        }
        $fp = fopen($details['iaf_filename'], 'w');
        fwrite($fp, $details['iaf_file']);
        fclose($fp);
        echo "OK - File '" . $details['iaf_filename'] . "' successfully downloaded to the local directory\n";
    }


    /**
     * Checks whether the given user email address is assigned to the given
     * issue ID.
     *
     * @access  public
     * @param   resource $rpc_conn The connection resource
     * @param   array $auth Array of authentication information (email, password)
     * @param   integer $issue_id The issue ID
     */
    function checkIssueAssignment(&$rpc_conn, $auth, $issue_id)
    {
        // check if the confirmation message was already displayed
        if (!$GLOBALS['_displayed_confirmation']) {
            // check if the current user is allowed to change the given issue
            $msg = new XML_RPC_Message("mayChangeIssue", array(
                new XML_RPC_Value($auth[0], 'string'),
                new XML_RPC_Value($auth[1], 'string'),
                new XML_RPC_Value($issue_id, 'int')
            ));
            $result = $rpc_conn->send($msg);
            if ($result->faultCode()) {
                self::quit($result->faultString());
            }
            $may_change_issue = XML_RPC_decode($result->value());
            // if not, show confirmation message
            if ($may_change_issue != 'yes') {
                echo "WARNING: You are not currently assigned to issue #$issue_id.\n";
                self::promptConfirmation($rpc_conn, $auth, $issue_id, false);
            }
        }
    }


    /**
     * Checks whether the given user email address is allowed to work with the
     * given issue ID.
     *
     * @access  public
     * @param   resource $rpc_conn The connection resource
     * @param   array $auth Array of authentication information (email, password)
     * @param   integer $issue_id The issue ID
     * @param   array The issue details, if the user is allowed to work on it
     */
    function checkIssuePermissions(&$rpc_conn, $auth, $issue_id)
    {
        $projects = self::getUserAssignedProjects($rpc_conn, $auth);

        $msg = new XML_RPC_Message("getIssueDetails", array(new XML_RPC_Value($auth[0], 'string'), new XML_RPC_Value($auth[1], 'string'),
                        new XML_RPC_Value($issue_id, 'int')));
        $result = $rpc_conn->send($msg);
        if ($result->faultCode()) {
            self::quit($result->faultString());
        }
        $details = XML_RPC_decode($result->value());

        foreach ($details as $k => $v) {
            $details[$k] = base64_decode($v);
        }

        // check if the issue the user is trying to change is inside a project viewable to him
        $found = 0;
        $nprojects = count($projects);
        for ($i = 0; $i < $nprojects; $i++) {
            if ($details['iss_prj_id'] == $projects[$i]['id']) {
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
     * @access  public
     * @param   resource $rpc_conn The connection resource
     * @param   array $auth Array of authentication information (email, password)
     * @param   integer $issue_id The issue ID
     * @param   string $developer The email address of the assignee
     */
    function assignIssue(&$rpc_conn, $auth, $issue_id, $developer)
    {
        // check if the given email address is indeed an email
        if (!strstr($developer, '@')) {
            self::quit("The third argument for this command needs to be a valid email address");
        }
        $details = self::checkIssuePermissions($rpc_conn, $auth, $issue_id);

        $params = array(
            new XML_RPC_Value($auth[0], 'string'),
            new XML_RPC_Value($auth[1], 'string'),
            new XML_RPC_Value($issue_id, 'int'),
            new XML_RPC_Value($details['iss_prj_id'], 'int'),
            new XML_RPC_Value($developer)
        );
        $msg = new XML_RPC_Message("assignIssue", $params);
        $result = $rpc_conn->send($msg);
        if ($result->faultCode()) {
            self::quit($result->faultString());
        }
        echo "OK - Issue #$issue_id successfully assigned to '$developer'\n";
    }


    /**
     * Method used to assign an issue to the current user and set status to 'assigned'.
     * If issue is already assigned to someone else, this will fail.
     *
     * @access  public
     * @param   resource $rpc_conn The connection resource
     * @param   array $auth Array of authentication information (email, password)
     * @param   integer $issue_id The issue ID
     */
    function takeIssue(&$rpc_conn, $auth, $issue_id)
    {
        $details = self::checkIssuePermissions($rpc_conn, $auth, $issue_id);

        $params = array(
            new XML_RPC_Value($auth[0], 'string'),
            new XML_RPC_Value($auth[1], 'string'),
            new XML_RPC_Value($issue_id, 'int'),
            new XML_RPC_Value($details['iss_prj_id'], 'int'),
        );
        $msg = new XML_RPC_Message("takeIssue", $params);
        $result = $rpc_conn->send($msg);
        if ($result->faultCode()) {
            self::quit($result->faultString());
        }
        echo "OK - Issue #$issue_id successfully taken.\n";
    }


    /**
     * Method used to add an authorized replier
     *
     * @access  public
     * @param   resource $rpc_conn The connection resource
     * @param   array $auth Array of authentication information (email, password)
     * @param   integer $issue_id The issue ID
     * @param   string $new_replier The email address of the assignee
     */
    function addAuthorizedReplier(&$rpc_conn, $auth, $issue_id, $new_replier)
    {
        // check if the given email address is indeed an email
        if (!strstr($new_replier, '@')) {
            self::quit("The third argument for this command needs to be a valid email address");
        }
        $details = self::checkIssuePermissions($rpc_conn, $auth, $issue_id);

        $params = array(
            new XML_RPC_Value($auth[0], 'string'),
            new XML_RPC_Value($auth[1], 'string'),
            new XML_RPC_Value($issue_id, 'int'),
            new XML_RPC_Value($details['iss_prj_id'], 'int'),
            new XML_RPC_Value($new_replier)
        );
        $msg = new XML_RPC_Message("addAuthorizedReplier", $params);
        $result = $rpc_conn->send($msg);
        if ($result->faultCode()) {
            self::quit($result->faultString());
        }
        echo "OK - '$new_replier' successfully added as an authorized replier to issue #$issue_id\n";
    }


    /**
     * Method used to change the status of an issue.
     *
     * @access  public
     * @param   resource $rpc_conn The connection resource
     * @param   array $auth Array of authentication information (email, password)
     * @param   integer $issue_id The issue ID
     * @param   string $new_status The new status title
     */
    function setIssueStatus(&$rpc_conn, $auth, $issue_id, $new_status)
    {
        $details = self::checkIssuePermissions($rpc_conn, $auth, $issue_id);
        self::checkIssueAssignment($rpc_conn, $auth, $issue_id);

        // check if the issue already is set to the new status
        if ((strtolower($details['sta_title']) == strtolower($new_status)) ||
                (strtolower($details['sta_abbreviation']) == strtolower($new_status))) {
            self::quit("Issue #$issue_id is already set to status '" . $details['sta_title'] . "'");
        }

        // check if the given status is a valid option
        $msg = new XML_RPC_Message("getAbbreviationAssocList", array(
            new XML_RPC_Value($auth[0], 'string'),
            new XML_RPC_Value($auth[1], 'string'),
            new XML_RPC_Value($details['iss_prj_id'], 'int'),
            new XML_RPC_Value(FALSE, 'boolean'),
        ));
        $result = $rpc_conn->send($msg);
        if ($result->faultCode()) {
            self::quit($result->faultString());
        }
        $statuses = XML_RPC_decode($result->value());
        $titles = array_map('strtolower', array_values($statuses));
        $abbreviations = array_map('strtolower', array_keys($statuses));
        if ((!in_array(strtolower($new_status), $titles)) &&
                (!in_array(strtolower($new_status), $abbreviations))) {
            self::quit("Status '$new_status' could not be matched against the list of available statuses");
        }

        // if the user is passing an abbreviation, use the real title instead
        if (in_array(strtolower($new_status), $abbreviations)) {
            $index = array_search(strtolower($new_status), $abbreviations);
            $new_status = $titles[$index];
        }
        $params = array(
            new XML_RPC_Value($auth[0], 'string'),
            new XML_RPC_Value($auth[1], 'string'),
            new XML_RPC_Value($issue_id, 'int'),
            new XML_RPC_Value($new_status)
        );
        $msg = new XML_RPC_Message("setIssueStatus", $params);
        $result = $rpc_conn->send($msg);
        if ($result->faultCode()) {
            self::quit($result->faultString());
        }
        echo "OK - Status successfully changed to '$new_status' on issue #$issue_id\n";
    }


    /**
     * Method used to add a time tracking entry to an existing issue.
     *
     * @access  public
     * @param   resource $rpc_conn The connection resource
     * @param   array $auth Array of authentication information (email, password)
     * @param   integer $issue_id The issue ID
     * @param   string $time_spent The time spent in minutes
     */
    function addTimeEntry(&$rpc_conn, $auth, $issue_id, $time_spent)
    {
        self::checkIssuePermissions($rpc_conn, $auth, $issue_id);
        self::checkIssueAssignment($rpc_conn, $auth, $issue_id);

        // list the time tracking categories
        $params = array(
            new XML_RPC_Value($auth[0], 'string'),
            new XML_RPC_Value($auth[1], 'string'),
            new XML_RPC_Value($issue_id, 'int'),
        );
        $msg = new XML_RPC_Message("getTimeTrackingCategories", $params);
        $result = $rpc_conn->send($msg);
        if ($result->faultCode()) {
            self::quit($result->faultString());
        }
        $cats = XML_RPC_decode($result->value());

        $prompt = "Which time tracking category would you like to associate with this time entry?\n";
        foreach ($cats as $id => $title) {
            $prompt .= sprintf(" [%s] => %s\n", $id, $title);
        }
        $prompt .= "Please enter the number of the time tracking category";
        $cat_id = Misc::prompt($prompt, false);
        if (!in_array($cat_id, array_keys($cats))) {
            self::quit("The selected time tracking category number didn't match any existing category");
        }

        $prompt = "Please enter a quick summary of what you worked on";
        $summary = Misc::prompt($prompt, false);

        $params = array(
            new XML_RPC_Value($auth[0], 'string'),
            new XML_RPC_Value($auth[1], 'string'),
            new XML_RPC_Value($issue_id, 'int'),
            new XML_RPC_Value($cat_id, 'int'),
            new XML_RPC_Value($summary),
            new XML_RPC_Value($time_spent, 'int')
        );
        $msg = new XML_RPC_Message("recordTimeWorked", $params);
        $result = $rpc_conn->send($msg);
        if ($result->faultCode()) {
            self::quit($result->faultString());
        }
        echo "OK - Added time tracking entry to issue #$issue_id\n";
    }


    /**
     * Method used to print the current details for a given issue.
     *
     * @access  public
     * @param   resource $rpc_conn The connection resource
     * @param   array $auth Array of authentication information (email, password)
     * @param   integer $issue_id The issue ID
     */
    function printIssueDetails(&$rpc_conn, $auth, $issue_id)
    {
        $details = self::checkIssuePermissions($rpc_conn, $auth, $issue_id);

        $msg = '';
        if (!empty($details["quarantine"]["iqu_status"])) {
            $msg .= "        WARNING: Issue is currently quarantined!";
            if (!empty($details["quarantine"]["iqu_expiration"])) {
                $msg .= " Quarantine expires in " . $details["quarantine"]["time_till_expiration"];
            }
            $msg .= "\n";
        }
        $msg .= "        Issue #: $issue_id
        Summary: " . $details['iss_summary'] . "
         Status: " . $details['sta_title'] . "
     Assignment: " . $details['assignments'] . "
 Auth. Repliers: " . @implode(', ', $details['authorized_names']) . "
       Reporter: " . $details['reporter'];
        if (@isset($details['customer_info'])) {
            $msg .= "
       Customer: " . @$details['customer_info']['customer_name'] . "
  Support Level: " . @$details['customer_info']['support_level'] . "
Support Options: " . @$details['customer_info']['support_options'] . "
          Phone: " . $details['iss_contact_phone'] . "
       Timezone: " . $details['iss_contact_timezone'] . "
Account Manager: " . @$details['customer_info']['account_manager'];
        }
        $msg .= "
  Last Response: " . $details['iss_last_response_date'] . "
   Last Updated: " . $details['iss_updated_date'] . "\n";
        echo $msg;
    }


    /**
     * Method used to print the list of open issues.
     *
     * @access  public
     * @param   resource $rpc_conn The connection resource
     * @param   array $auth Array of authentication information (email, password)
     * @param   string $show_all_issues Whether to show all open issues or just the ones assigned to the current user
     * @param   string $status The status that should be used to restrict the results
     */
    function printOpenIssues(&$rpc_conn, $auth, $show_all_issues, $status)
    {
        $project_id = self::promptProjectSelection($rpc_conn, $auth);
        // check the status option
        // check if the given status is a valid option
        if (!empty($status)) {
            $msg = new XML_RPC_Message("getAbbreviationAssocList", array(
                new XML_RPC_Value($auth[0], 'string'),
                new XML_RPC_Value($auth[1], 'string'),
                new XML_RPC_Value($project_id, 'int'),
                new XML_RPC_Value(TRUE, 'boolean'),
            ));
            $result = $rpc_conn->send($msg);
            if ($result->faultCode()) {
                self::quit($result->faultString());
            }
            $statuses = XML_RPC_decode($result->value());
            $titles = array_map('strtolower', array_values($statuses));
            $abbreviations = array_map('strtolower', array_keys($statuses));
            if ((!in_array(strtolower($status), $titles)) &&
                    (!in_array(strtolower($status), $abbreviations))) {
                self::quit("Status '$status' could not be matched against the list of available statuses");
            }
            // if the user is passing an abbreviation, use the real title instead
            if (in_array(strtolower($status), $abbreviations)) {
                $status = $statuses[strtoupper($status)];
            }
        }

        $msg = new XML_RPC_Message("getOpenIssues", array(
            new XML_RPC_Value($auth[0], 'string'),
            new XML_RPC_Value($auth[1], 'string'),
            new XML_RPC_Value($project_id, 'int'),
            new XML_RPC_Value($show_all_issues, 'boolean'),
            new XML_RPC_Value($status)
        ));
        $result = $rpc_conn->send($msg);
        if ($result->faultCode()) {
            self::quit($result->faultString());
        }
        $issues = XML_RPC_decode($result->value());
        if (!empty($status)) {
            echo "The following issues are set to status '$status':\n";
        } else {
            echo "The following issues are still open:\n";
        }
        foreach ($issues as $issue) {
            echo "- #" . $issue['issue_id'] . " - " . $issue['summary'] . " (" . $issue['status'] . ")";
            if (!empty($issue['assigned_users'])) {
                echo " - (" . $issue['assigned_users'] . ")";
            } else {
                echo " - (unassigned)";
            }
            echo "\n";
        }
    }


    /**
     * Method used to get the list of projects assigned to a given email address.
     *
     * @access  public
     * @param   resource $rpc_conn The connection resource
     * @param   array $auth Array of authentication information (email, password)
     * @param   boolean $only_customer_projects Whether to only include projects with customer integration or not
     * @return  array The list of projects
     */
    function getUserAssignedProjects(&$rpc_conn, $auth, $only_customer_projects = FALSE)
    {
        $msg = new XML_RPC_Message("getUserAssignedProjects", array(
            new XML_RPC_Value($auth[0], 'string'),
            new XML_RPC_Value($auth[1], 'string'),
            new XML_RPC_Value($only_customer_projects, 'boolean')
        ));
        $result = $rpc_conn->send($msg);
        if ($result->faultCode()) {
            self::quit($result->faultString());
        }
        return XML_RPC_decode($result->value());
    }


    /**
     * Method used to prompt the current user to select a project.
     *
     * @access  public
     * @param   resource $rpc_conn The connection resource
     * @param   array $auth Array of authentication information (email, password)
     * @param   boolean $only_customer_projects Whether to only include projects with customer integration or not
     * @return  integer The project ID
     */
    function promptProjectSelection(&$rpc_conn, $auth, $only_customer_projects = FALSE)
    {
        // list the projects that this user is assigned to
        $projects = self::getUserAssignedProjects($rpc_conn, $auth, $only_customer_projects);

        if (count($projects) > 1) {
            // need to ask which project this person is asking about
            $prompt = "For which project do you want this action to apply to?\n";
            $nprojects = count($projects);
            for ($i = 0; $i < $nprojects; $i++) {
                $prompt .= sprintf(" [%s] => %s\n", $projects[$i]['id'], $projects[$i]['title']);
            }
            $prompt .= "Please enter the number of the project";
            $project_id = Misc::prompt($prompt, false);
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
        return $project_id;
    }


    /**
     * Method used to print the available statuses associated with the
     * currently selected project.
     *
     * @access  public
     * @param   resource $rpc_conn The connection resource
     * @param   array $auth Array of authentication information (email, password)
     */
    function printStatusList(&$rpc_conn, $auth)
    {
        $project_id = self::promptProjectSelection($rpc_conn, $auth);
        $msg = new XML_RPC_Message("getAbbreviationAssocList", array(
            new XML_RPC_Value($auth[0], 'string'),
            new XML_RPC_Value($auth[1], 'string'),
            new XML_RPC_Value($project_id, 'int'),
            new XML_RPC_Value(TRUE, 'boolean'),
        ));
        $result = $rpc_conn->send($msg);
        if ($result->faultCode()) {
            self::quit($result->faultString());
        }
        $items = XML_RPC_decode($result->value());
        echo "Available Statuses:\n";
        foreach ($items as $abbreviation => $title) {
            echo "$abbreviation => $title\n";
        }
    }


    /**
     * Method used to print the list of developers.
     *
     * @access  public
     * @param   resource $rpc_conn The connection resource
     * @param   string $email The email address of the current user
     */
    function printDeveloperList(&$rpc_conn, $auth)
    {
        $project_id = self::promptProjectSelection($rpc_conn, $auth);
        $msg = new XML_RPC_Message("getDeveloperList", array(
            new XML_RPC_Value($auth[0], 'string'),
            new XML_RPC_Value($auth[1], 'string'),
            new XML_RPC_Value($project_id, "int")
        ));
        $result = $rpc_conn->send($msg);
        if ($result->faultCode()) {
            self::quit($result->faultString());
        }
        $developers = XML_RPC_decode($result->value());
        echo "Available Developers:\n";
        foreach ($developers as $name => $email) {
            echo "-> $name - $email\n";
        }
    }


    /**
     * Method used to list emails for a given issue.
     *
     * @access  public
     * @param   resource $rpc_conn The connection resource
     * @param   array $auth Array of authentication information (email, password)
     * @param   integer $issue_id The issue ID
     */
    function listEmails(&$rpc_conn, $auth, $issue_id)
    {
        self::checkIssuePermissions($rpc_conn, $auth, $issue_id);

        $msg = new XML_RPC_Message("getEmailListing", array(new XML_RPC_Value($auth[0], 'string'), new XML_RPC_Value($auth[1], 'string'),
                        new XML_RPC_Value($issue_id, "int")));
        $result = $rpc_conn->send($msg);
        if ($result->faultCode()) {
            self::quit($result->faultString());
        }
        $emails = XML_RPC_decode($result->value());
        if (!is_array($emails) || count($emails) < 1) {
            echo "No emails for this issue\n";
            exit;
        }
        // since xml-rpc has issues, we have to base64 decode everything
        $nemails = count($emails);
        for ($i = 0; $i < $nemails; $i++) {
            foreach ($emails[$i] as $key => $val) {
                $emails[$i][$key] = base64_decode($val);
            }
            $emails[$i]["id"] = ($i+1);
        }
        $format = array(
            "id" => array(
                "width" => 3,
                "title" => "ID"
            ),
            "sup_date" => array(
                "width" => 30,
                "title" => "Date"
            ),
            "sup_from" => array(
                "width" => 24,
                "title" => "From"
            ),
            "sup_cc" => array(
                "width" => 24,
                "title" => "CC"
            ),
            "sup_subject" => array(
                "width" => 30,
                "title" => "Subject"
            )
        );
        self::printTable($format, $emails);
    }


    /**
     * Method to show the contents of an email.
     *
     * @access  public
     * @param   resource $rpc_conn The connection resource
     * @param   array $auth Array of authentication information (email, password)
     * @param   integer $issue_id The issue ID
     * @param   integer $email_id The sequential id of the email to view
     * @param   boolean $display_full If the full email should be displayed.
     */
    function printEmail(&$rpc_conn, $auth, $issue_id, $email_id, $display_full)
    {
        self::checkIssuePermissions($rpc_conn, $auth, $issue_id);

        $msg = new XML_RPC_Message("getEmail", array(new XML_RPC_Value($auth[0], 'string'), new XML_RPC_Value($auth[1], 'string'),
                    new XML_RPC_Value($issue_id, "int"), new XML_RPC_Value($email_id, "int")));
        $result = $rpc_conn->send($msg);
        if ($result->faultCode()) {
            self::quit($result->faultString());
        }
        $email = XML_RPC_decode($result->value());
        // since xml-rpc has issues, we have to base64 decode everything
        foreach ($email as $key => $val) {
            $email[$key] = base64_decode($val);
        }
        if ($display_full) {
            echo $email["seb_full_email"];
        } else {
            echo sprintf("%15s: %s\n", "Date", $email["sup_date"]);
            echo sprintf("%15s: %s\n", "From", $email["sup_from"]);
            echo sprintf("%15s: %s\n", "To", $email["sup_to"]);
            echo sprintf("%15s: %s\n", "CC", $email["sup_cc"]);
            echo sprintf("%15s: %s\n", "Attachments?", (($email["sup_has_attachment"] == 1) ? 'yes' : 'no'));
            echo sprintf("%15s: %s\n", "Subject", $email["sup_subject"]);
            echo "------------------------------------------------------------------------\n";
            echo $email["message"];
        }
    }


    /**
     * Method used to list notes for a given issue.
     *
     * @access  public
     * @param   resource $rpc_conn The connection resource
     * @param   array $auth Array of authentication information (email, password)
     * @param   integer $issue_id The issue ID
     */
    function listNotes(&$rpc_conn, $auth, $issue_id)
    {
        self::checkIssuePermissions($rpc_conn, $auth, $issue_id);

        $msg = new XML_RPC_Message("getNoteListing", array(new XML_RPC_Value($auth[0], 'string'), new XML_RPC_Value($auth[1], 'string'),
                        new XML_RPC_Value($issue_id, "int")));
        $result = $rpc_conn->send($msg);
        if ($result->faultCode()) {
            self::quit($result->faultString());
        }
        $notes = XML_RPC_decode($result->value());
        // since xml-rpc has issues, we have to base64 decode everything
        $nnotes = count($notes);
        for ($i = 0; $i < $nnotes; $i++) {
            foreach ($notes[$i] as $key => $val) {
                $notes[$i][$key] = base64_decode($val);
            }
            if ($notes[$i]["has_blocked_message"] == 1) {
                $notes[$i]["not_title"] = '(BLOCKED) ' . $notes[$i]["not_title"];
            }
            $notes[$i]["id"] = ($i+1);
        }
        if (count($notes) < 1) {
            echo "No notes for this issue\n";
            exit;
        }
        $format = array(
            "id" => array(
                "width" => 3,
                "title" => "ID"
            ),
            "usr_full_name" => array(
                "width" => 24,
                "title" => "User"
            ),
            "not_title" => array(
                "width" => 50,
                "title" => "Title"
            ),
            "not_created_date" => array(
                "width" => 30,
                "title" => "Date"
            )
        );
        self::printTable($format, $notes);
    }


    /**
     * Method to show the contents of a note.
     *
     * @access  public
     * @param   resource $rpc_conn The connection resource
     * @param   array $auth Array of authentication information (email, password)
     * @param   integer $issue_id The issue ID
     * @param   integer $note_id The sequential id of the note to view
     */
    function printNote(&$rpc_conn, $auth, $issue_id, $note_id)
    {
        self::checkIssuePermissions($rpc_conn, $auth, $issue_id);

        $note = self::getNote($rpc_conn, $auth, $issue_id, $note_id);
        echo sprintf("%15s: %s\n", "Date", $note["not_created_date"]);
        echo sprintf("%15s: %s\n", "From", $note["not_from"]);
        echo sprintf("%15s: %s\n", "Title", $note["not_title"]);
        echo "------------------------------------------------------------------------\n";
        echo $note["not_note"];
    }


    /**
     * Returns the contents of a note via XML-RPC.
     *
     * @access  public
     * @param   resource $rpc_conn The connection resource
     * @param   array $auth Array of authentication information (email, password)
     * @param   integer $issue_id The issue ID
     * @param   integer $note_id The sequential id of the note to view
     * @return  array An array containg note details.
     */
    function getNote(&$rpc_conn, $auth, $issue_id, $note_id)
    {
        $msg = new XML_RPC_Message("getNote", array(new XML_RPC_Value($auth[0], 'string'), new XML_RPC_Value($auth[1], 'string'),
                        new XML_RPC_Value($issue_id, "int"), new XML_RPC_Value($note_id, "int")));
        $result = $rpc_conn->send($msg);
        if ($result->faultCode()) {
            self::quit($result->faultString());
        }
        $note = XML_RPC_decode($result->value());
        // since xml-rpc has issues, we have to base64 decode everything
        if (is_array($note)) {
            foreach ($note as $key => $val) {
                $note[$key] = base64_decode($val);
            }
        }
        return $note;
    }


    /**
     * Converts a note into a draft or an email.
     *
     * @access  public
     * @param   resource $rpc_conn The connection source
     * @param   array $auth Array of authentication information (email, password)
     * @param   integer $issue_id The issue ID
     * @param   integer $note_id The sequential id of the note to view
     * @param   string $target What this note should be converted too, a draft or an email.
     * @param   boolean $authorize_sender If the sender should be added to the authorized repliers list.
     */
    function convertNote(&$rpc_conn, $auth, $issue_id, $note_id, $target, $authorize_sender)
    {
        self::checkIssuePermissions($rpc_conn, $auth, $issue_id);
        self::checkIssueAssignment($rpc_conn, $auth, $issue_id);

        $note_details = self::getNote($rpc_conn, $auth, $issue_id, $note_id);
        if (count($note_details) < 2) {
            self::quit("Note #$note_id does not exist for issue #$issue_id");
        } elseif ($note_details["has_blocked_message"] != 1) {
            self::quit("Note #$note_id does not have a blocked message attached so cannot be converted");
        }
        $msg = new XML_RPC_Message("convertNote", array(
            new XML_RPC_Value($auth[0], 'string'),
            new XML_RPC_Value($auth[1], 'string'),
            new XML_RPC_Value($issue_id, "int"),
            new XML_RPC_Value($note_details["not_id"], "int"),
            new XML_RPC_Value($target, "string"),
            new XML_RPC_Value($authorize_sender, 'boolean')
        ));
        $result = $rpc_conn->send($msg);
        if ($result->faultCode()) {
            self::quit($result->faultString());
        }
        $message = XML_RPC_decode($result->value());
        if ($message == "OK") {
            echo "OK - Note successfully converted to $target\n";
        }
    }


    /**
     * Fetches the weekly report for the current developer for the specified week.
     *
     * @access  public
     * @param   resource $rpc_conn The connection resource
     * @param   array $auth Array of authentication information (email, password)
     * @param   integer $week The week for the report. If start and end date are set, this is ignored.
     * @param   string $start_date The start date of the report. (optional)
     * @param   string $end_date The end_date of the report. (optional)
     * @param   boolean If closed issues should be separated from other issues.
     */
    function getWeeklyReport(&$rpc_conn, $auth, $week, $start_date = '', $end_date = '', $separate_closed = false)
    {
        $msg = new XML_RPC_Message("getWeeklyReport", array(
            new XML_RPC_Value($auth[0], 'string'),
            new XML_RPC_Value($auth[1], 'string'),
            new XML_RPC_Value($week, "int"),
            new XML_RPC_Value($start_date, "string"),
            new XML_RPC_Value($end_date, "string"),
            new XML_RPC_Value($separate_closed ? 1 : 0, 'int'),
        ));
        $result = $rpc_conn->send($msg);
        if ($result->faultCode()) {
            self::quit($result->faultString());
        } else {
            $ret = XML_RPC_decode($result->value());
            echo base64_decode($ret);
        }
    }


    /**
     * Clocks a user in/out of the system.
     *
     * @access  public
     * @param   resource $rpc_conn The connection resource
     * @param   array $auth Array of authentication information (email, password)
     * @param   string $action If the user is clocking in or out.
     */
    function timeClock(&$rpc_conn, $auth, $action)
    {
        $msg = new XML_RPC_Message("timeClock", array(
            new XML_RPC_Value($auth[0], 'string'),
            new XML_RPC_Value($auth[1], 'string'),
            new XML_RPC_Value($action, "string")
        ));
        $result = $rpc_conn->send($msg);
        if ($result->faultCode()) {
            self::quit($result->faultString());
        } else {
            echo XML_RPC_decode($result->value());
        }
    }


    /**
     * Lists drafts associated with an issue.
     *
     * @access  public
     * @param   resource $rpc_conn The connection resource
     * @param   array $auth Array of authentication information (email, password)
     * @param   integer $issue_id The issue ID
     */
    function listDrafts(&$rpc_conn, $auth, $issue_id)
    {
        self::checkIssuePermissions($rpc_conn, $auth, $issue_id);

        $msg = new XML_RPC_Message("getDraftListing", array(new XML_RPC_Value($auth[0], 'string'), new XML_RPC_Value($auth[1], 'string'),
                        new XML_RPC_Value($issue_id, "int")));
        $result = $rpc_conn->send($msg);
        if ($result->faultCode()) {
            self::quit($result->faultString());
        }
        $drafts = XML_RPC_decode($result->value());
        // since xml-rpc has issues, we have to base64 decode everything
        $ndrafts = count($drafts);
        for ($i = 0; $i < $ndrafts; $i++) {
            foreach ($drafts[$i] as $key => $val) {
                $drafts[$i][$key] = base64_decode($val);
            }
            $drafts[$i]["id"] = ($i+1);
        }
        if (count($drafts) < 1) {
            echo "No drafts for this issue\n";
            exit;
        }
        $format = array(
            "id" => array(
                "width" => 3,
                "title" => "ID"
            ),
            "from" => array(
                "width" => 24,
                "title" => "From"
            ),
            "to" => array(
                "width" => 24,
                "title" => "To"
            ),
            "emd_subject" => array(
                "width" => 30,
                "title" => "Title"
            ),
            "emd_updated_date" => array(
                "width" => 30,
                "title" => "Date"
            )
        );
        self::printTable($format, $drafts);
    }


    /**
     * Method to show the contents of a draft.
     *
     * @access  public
     * @param   resource $rpc_conn The connection resource
     * @param   array $auth Array of authentication information (email, password)
     * @param   integer $issue_id The issue ID
     * @param   integer $note_id The sequential id of the draft to view
     */
    function printDraft(&$rpc_conn, $auth, $issue_id, $draft_id)
    {
        self::checkIssuePermissions($rpc_conn, $auth, $issue_id);

        $draft = self::getDraft($rpc_conn, $auth, $issue_id, $draft_id);
        echo sprintf("%15s: %s\n", "Date", $draft["emd_updated_date"]);
        echo sprintf("%15s: %s\n", "From", $draft["from"]);
        echo sprintf("%15s: %s\n", "To", $draft["to"]);
        if (!empty($draft['cc'])) {
            echo sprintf("%15s: %s\n", "Cc", $draft["cc"]);
        }
        echo sprintf("%15s: %s\n", "Title", $draft["emd_subject"]);
        echo "------------------------------------------------------------------------\n";
        echo $draft["emd_body"];
    }


    /**
     * Returns the contents of a draft via XML-RPC.
     *
     * @access  public
     * @param   resource $rpc_conn The connection resource
     * @param   array $auth Array of authentication information (email, password)
     * @param   integer $issue_id The issue ID
     * @param   integer $draft_id The sequential id of the draft to view
     * @return  array An array containg draft details.
     */
    function getDraft(&$rpc_conn, $auth, $issue_id, $draft_id)
    {
        $msg = new XML_RPC_Message("getDraft", array(new XML_RPC_Value($auth[0], 'string'), new XML_RPC_Value($auth[1], 'string'),
                        new XML_RPC_Value($issue_id, "int"), new XML_RPC_Value($draft_id, "int")));
        $result = $rpc_conn->send($msg);
        if ($result->faultCode()) {
            self::quit($result->faultString());
        }
        $draft = XML_RPC_decode($result->value());
        // since xml-rpc has issues, we have to base64 decode everything
        if (is_array($draft)) {
            foreach ($draft as $key => $val) {
                $draft[$key] = base64_decode($val);
            }
        }
        return $draft;
    }


    /**
     * Converts a draft to an email and sends it.
     *
     * @access  public
     * @param   resource $rpc_conn The connection resource
     * @param   array $auth Array of authentication information (email, password)
     * @param   integer $issue_id The issue ID
     * @param   integer $draft_id The sequential id of the draft to send
     * @return  array An array containg draft details.
     */
    function sendDraft(&$rpc_conn, $auth, $issue_id, $draft_id)
    {
        $msg = new XML_RPC_Message("sendDraft", array(new XML_RPC_Value($auth[0], 'string'), new XML_RPC_Value($auth[1], 'string'),
                        new XML_RPC_Value($issue_id, "int"), new XML_RPC_Value($draft_id, "int")));
        $result = $rpc_conn->send($msg);
        if ($result->faultCode()) {
            self::quit($result->faultString());
        }
        echo XML_RPC_decode($result->value());
    }


    /**
     * Marks an issue as redeemed incident
     *
     * @access  public
     * @param   resource $rpc_conn The connection resource
     * @param   array $auth Array of authentication information (email, password)
     * @param   integer $issue_id The issue ID
     */
    function redeemIssue(&$rpc_conn, $auth, $issue_id)
    {
        self::checkIssuePermissions($rpc_conn, $auth, $issue_id);
        self::checkIssueAssignment($rpc_conn, $auth, $issue_id);

        $types = self::promptIncidentTypes($rpc_conn, $auth, $issue_id);
        foreach ($types as $type_id => $type_value) {
            $types[$type_id] = new XML_RPC_Value($type_value, 'string');
        }

        $params = array(
            new XML_RPC_Value($auth[0], 'string'),
            new XML_RPC_Value($auth[1], 'string'),
            new XML_RPC_Value($issue_id, 'int'),
            new XML_RPC_Value($types, 'struct')
        );
        $msg = new XML_RPC_Message("redeemIssue", $params);
        $result = $rpc_conn->send($msg);
        if ($result->faultCode()) {
            self::quit($result->faultString());
        }
        echo "OK - Issue #$issue_id successfully marked as redeemed incident.\n";
    }


    /**
     * Un-marks an issue as redeemed incident
     *
     * @access  public
     * @param   resource $rpc_conn The connection resource
     * @param   array $auth Array of authentication information (email, password)
     * @param   integer $issue_id The issue ID
     */
    function unredeemIssue(&$rpc_conn, $auth, $issue_id)
    {
        self::checkIssuePermissions($rpc_conn, $auth, $issue_id);
        self::checkIssueAssignment($rpc_conn, $auth, $issue_id);

        $types = self::promptIncidentTypes($rpc_conn, $auth, $issue_id, true);
        foreach ($types as $type_id => $type_value) {
            $types[$type_id] = new XML_RPC_Value($type_value, 'string');
        }

        $params = array(
            new XML_RPC_Value($auth[0], 'string'),
            new XML_RPC_Value($auth[1], 'string'),
            new XML_RPC_Value($issue_id, 'int'),
            new XML_RPC_Value($types, 'struct')
        );
        $msg = new XML_RPC_Message("unredeemIssue", $params);
        $result = $rpc_conn->send($msg);
        if ($result->faultCode()) {
            self::quit($result->faultString());
        }
        echo "OK - Issue #$issue_id successfully marked as unredeemed incident.\n";
    }


    /**
     * Returns the list of incident types available.
     *
     * @access  public
     * @param   resource $rpc_conn The connection resource
     * @param   array $auth Array of authentication information (email, password)
     * @param   integer $issue_id The issue ID
     * @param   boolean $redeemed_only If this should only show items that have been redeemed.
     */
    function promptIncidentTypes(&$rpc_conn, $auth, $issue_id, $redeemed_only = false)
    {
        $params = array(
            new XML_RPC_Value($auth[0], 'string'),
            new XML_RPC_Value($auth[1], 'string'),
            new XML_RPC_Value($issue_id, 'int'),
            new XML_RPC_Value($redeemed_only, 'boolean')
        );
        $msg = new XML_RPC_Message("getIncidentTypes", $params);
        $result = $rpc_conn->send($msg);
        if ($result->faultCode()) {
            self::quit($result->faultString());
        }
        $types =  XML_RPC_decode($result->value());
        if (count($types) < 1) {
            if ($redeemed_only) {
                self::quit("No incident types have been redeemed for this issue");
            } else {
                self::quit("All incident types have already been redeemed for this issue");
            }
        }
        $prompt = "Please enter a comma seperated list of incident types to ";
        if ($redeemed_only) {
            $prompt .= "un";
        }
        $prompt .= "redeem for this issue.\n";
        foreach ($types as $id => $data) {
            $prompt .= sprintf(" [%s] => %s (Total: %s; Left: %s)\n", $id, $data['title'], $data['total'], ($data['total'] - $data['redeemed']));
        }
        $requested_types = Misc::prompt($prompt, false);
        $requested_types = explode(',', $requested_types);
        if (count($requested_types) < 1) {
            self::quit("Please enter a comma seperated list of issue types");
        } else {
            $type_keys = array_keys($types);
            foreach ($requested_types as $type_id) {
                if (!in_array($type_id, $type_keys)) {
                    self::quit("Input '$type_id' is not a valid incident type");
                }
            }
            return $requested_types;
        }
    }


    /**
     * Method to print data in a formatted table, according to the $format array.
     *
     * @param   array $format An array containing how to format the data
     * @param   array $data An array of data to be printed
     */
    function printTable($format, $data)
    {
        // loop through the fields, printing out the header row
        $firstRow = '';
        $secondRow = '';
        foreach ($format as $column) {
            $firstRow .= sprintf("%-" . $column["width"] . "s", $column["title"]) . " ";
            $secondRow .= sprintf("%-'-" . $column["width"] . "s","") . " ";
        }
        echo $firstRow . "\n" . $secondRow . "\n";
        // print out data
        $ndata = count($data);
        for ($i = 0; $i < $ndata; $i++) {
            foreach ($format as $key => $column) {
                echo sprintf("%-" . $column["width"] . "s", substr($data[$i][$key], 0, $column["width"])) . " ";
            }
            echo "\n";
        }
    }


    /**
     * Method used to print a confirmation prompt with the current details
     * of the given issue. The $command parameter can be used to determine what type of
     * confirmation to show to the user.
     *
     * @access  public
     * @param   resource $rpc_conn The connection resource
     * @param   array $auth Array of authentication information (email, password)
     * @param   integer $issue_id The issue ID
     * @param   string $args The arguments passed to this script
     */
    function promptConfirmation(&$rpc_conn, $auth, $issue_id, $args)
    {
        // this is needed to prevent multiple confirmations from being shown to the user
        $GLOBALS['_displayed_confirmation'] = true;
        // get summary, customer status and assignment of issue, then show confirmation prompt to user
        $msg = new XML_RPC_Message("getSimpleIssueDetails", array(new XML_RPC_Value($auth[0], 'string'), new XML_RPC_Value($auth[1], 'string'),
                    new XML_RPC_Value($issue_id, "int")));
        $result = $rpc_conn->send($msg);
        if ($result->faultCode()) {
            self::quit($result->faultString());
        } else {
            switch ($args[2]) {
                case 'convert-note':
                case 'cn':
                    $note_details = self::getNote($rpc_conn, $auth, $issue_id, $args[3]);
                    $msg = "These are the current details for issue #$issue_id, note #" . $args[3] . ":\n" .
                            "   Date: " . $note_details["not_created_date"] . "\n" .
                            "   From: " . $note_details["not_from"] . "\n" .
                            "  Title: " . $note_details["not_title"] . "\n" .
                            "Are you sure you want to convert this note into a " . $args[4] . "?";
                    break;
                default:
                    $details = XML_RPC_decode($result->value());
                    $msg = "These are the current details for issue #$issue_id:\n" .
                            "         Summary: " . $details['summary'] . "\n";
                    if (@!empty($details['customer'])) {
                        $msg .= "        Customer: " . $details['customer'] . "\n";
                    }
                    $msg .= "          Status: " . $details['status'] . "\n" .
                            "      Assignment: " . $details["assignments"] . "\n" .
                            "  Auth. Repliers: " . $details["authorized_names"] . "\n" .
                            "Are you sure you want to change this issue?";
            }
            $ret = Misc::prompt($msg, 'y');
            if (strtolower($ret) != 'y') {
                exit;
            }
        }
    }


    /**
     * Method used to check the authentication of the current user.
     *
     * @access  public
     * @param   resource $rpc_conn The connection resource
     * @param   string $email The email address of the current user
     * @param   string $password The password of the current user
     */
    function checkAuthentication(&$rpc_conn, $email, $password)
    {
        $msg = new XML_RPC_Message("isValidLogin", array(new XML_RPC_Value($email), new XML_RPC_Value($password)));
        $result = $rpc_conn->send($msg);
        if (!is_object($result)) {
            self::quit("result is not an object. This is most likely due connection problems or openssl/curl extension not loaded.");
        }
        if ($result->faultCode()) {
            self::quit($result->faultString());
        }
        $is_valid = XML_RPC_Decode($result->value());
        if ($is_valid != 'yes') {
            self::quit("Login information could not be authenticated");
        }
    }


    /**
     * Logs the current command
     *
     * @access  public
     * @param   resource $rpc_conn The connection resource
     * @param   array $auth Array of authentication information (email, password)
     * @param   string $command The command used to run this script.
     */
    function log(&$rpc_conn, $auth, $command)
    {
        $command = base64_encode($command);
        $msg = new XML_RPC_Message("logCommand", array(new XML_RPC_Value($auth[0], 'string'), new XML_RPC_Value($auth[1], 'string'), new XML_RPC_Value($command, 'string')));
        $result = $rpc_conn->send($msg);
        if ($result->faultCode()) {
            self::quit($result->faultString());
        }
    }


    /**
     * Method used to check whether the current execution needs to have a
     * confirmation message shown before performing the requested action or not.
     *
     * @access  public
     * @return  boolean
     */
    function isSafeExecution()
    {
        global $argv, $argc;
        if ($argv[count($argv) - 1] == '--safe') {
            array_pop($argv);
            return true;
        } else {
            return false;
        }
    }


    /**
     * Method used to print a usage statement for the command line interface.
     *
     * @access  public
     * @param   string $script The current script name
     */
    function usage($script)
    {
        $usage = array();
        $usage[] = array(
            "command"   =>  "<ticket_number>",
            "help"      =>  "View general details of an existing issue."
        );
        $usage[] = array(
            "command"   =>  "<ticket_number> assign <developer_email> [--safe]",
            "help"      =>  "Assign an issue to another developer."
        );
        $usage[] = array(
            "command"   =>  "<ticket_number> take [--safe]",
            "help"      =>  "Assign an issue to yourself and change status to 'Assigned'."
        );
        $usage[] = array(
            "command"   =>  array("<ticket_number> add-replier <user_email> [--safe]","<ticket_number> ar <user_email> [--safe]"),
            "help"      =>  "Adds the specified user to the list of authorized repliers."
        );
        $usage[] = array(
            "command"   =>  "<ticket_number> set-status <status> [--safe]",
            "help"      =>  "Sets the status of an issue to the desired value. If you are not sure
     about the available statuses, use command 'list-status' described below."
        );
        $usage[] = array(
            "command"   =>  "<ticket_number> add-time <time_worked> [--safe]",
            "help"      =>  "Records time worked to the time tracking tool of the given issue."
        );
        $usage[] = array(
            "command"   =>  "<ticket_number> close [--safe]",
            "help"      =>  "Marks an issue as closed."
        );
        $usage[] = array(
            "command"   =>  array("<ticket_number> list-files", "<ticket_number> lf"),
            "help"      =>  "List available attachments associated with the given issue.",
        );
        $usage[] = array(
            "command"   =>  array("<ticket_number> get-file <file_number>", "<ticket_number> gf <file_number>"),
            "help"      =>  "Download a specific file from the given issue."
        );
        $usage[] = array(
            "command"   =>  array("<ticket_number> list-emails","<ticket_number> le"),
            "help"      =>  "Lists emails from the given issue."
        );
        $usage[] = array(
            "command"   =>  array("<ticket_number> get-email <email_number> [--full]","<ticket_number> ge <email_number> [--full]"),
            "help"      =>  "Displays a specific email for the issue. If the optional --full parameter
     is specified, the full email including headers and attachments will be
     displayed."
        );
        $usage[] = array(
            "command"   =>  array("<ticket_number> list-notes","<ticket_number> ln"),
            "help"      =>  "Lists notes from the given issue."
        );
        $usage[] = array(
            "command"   =>  array("<ticket_number> get-note <note_number> [--full]","<ticket_number> gn <note_number>"),
            "help"      =>  "Displays a specific note for the issue."
        );
        $usage[] = array(
            "command"   =>  array("<ticket_number> convert-note <note_number> draft|email [authorize] [--safe]","<ticket_number> cn <note_number> draft|email [authorize] [--safe]"),
            "help"      =>  "Converts the specified note to a draft or an email.
    Use optional argument 'authorize' to add sender to authorized repliers list."
        );
        $usage[] = array(
            "command"   =>  array("<ticket_number> list-drafts","<ticket_number> ld"),
            "help"      =>  "Lists drafts from the given issue."
        );
        $usage[] = array(
            "command"   =>  array("<ticket_number> get-draft <draft_number>","<ticket_number> gd <draft_number>"),
            "help"      =>  "Displays a specific draft for the issue."
        );
        $usage[] = array(
            "command"   =>  array("<ticket_number> send-draft <draft_number>","<ticket_number> sd <draft_number>"),
            "help"      =>  "Converts a draft to an email and sends it out."
        );
        $usage[] = array(
            "command"   =>  array("<ticket_number> redeem"),
            "help"      =>  "Marks an issue as redeemed incident."
        );
        $usage[] = array(
            "command"   =>  array("<ticket_number> unredeem"),
            "help"      =>  "Un-marks an issue as redeemed incident."
        );
        $usage[] = array(
            "command"   =>  "developers",
            "help"      =>  "List all available developers' email addresses."
        );
        $usage[] = array(
            "command"   =>  "open-issues [<status>] [my]",
            "help"      =>  "List all issues that are not set to a status with a 'closed' context. Use
     optional argument 'my' if you just wish to see issues assigned to you."
        );
        $usage[] = array(
            "command"   =>  "list-status",
            "help"      =>  "List all available statuses in the system."
        );
        $usage[] = array(
            "command"   =>  "customer email|support|customer <value>",
            "help"      =>  "Looks up a customer's record information."
        );
        $usage[] = array(
            "command"   =>  array("weekly-report ([<week>] [--separate-closed])|([<start>] [<end>] [--separate-closed])", "wr ([<week>])|([<start>] [<end>] [--separate-closed])"),
            "help"      =>  "Fetches the weekly report. Week is specified as an integer with 0 representing
     the current week, -1 the previous week and so on. If the week is omitted it defaults
     to the current week. Alternately, a date range can be set. Dates should be in the format 'YYYY-MM-DD'."
        );
        $usage[] = array(
            "command"   =>  "clock [in|out]",
            "help"      =>  "Clocks you in or out of the system. When clocked out, no reminders will be sent to your account.
     If the in|out parameter is left off, your current status is displayed."
        );
        $script = basename($script);
        $usage_text = "";
        $explanation = "";
        foreach ($usage as $command_num => $this_command) {
            $item_num = sprintf("%2d.) ", ($command_num+1));
            $usage_text .= $item_num . "$script ";
            if (is_array($this_command["command"])) {
                $ncommands = count($this_command["command"]);
                for ($i = 0; $i < $ncommands; $i++) {
                    if ($i != 0) {
                        $usage_text .= "     $script ";
                    }
                    $usage_text .= $this_command["command"][$i] . "\n";
                }
            } else {
                $usage_text .= $this_command["command"] . "\n";
            }
            $explanation .= $item_num . $this_command["help"] . "\n\n";
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
     * @access  public
     * @param   string $msg The message that needs to be printed
     */
    function quit($msg)
    {
        die("Error - $msg. Run script with --help for usage information.\n");
    }
}
