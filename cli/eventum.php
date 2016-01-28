#!/usr/bin/php
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

define('APP_PATH', __DIR__ . '/..');
define('APP_NO_GETTEXT', true);
define('APP_DEFAULT_TIMEZONE', 'UTC');
define('APP_COOKIE', 'eventum');
require_once APP_PATH . '/autoload.php';

list($user_email, $user_password, $hostname, $port, $relative_url) = Command_Line::getEnvironmentSettings();
if (empty($port)) {
    $port = 80;
}
if (empty($relative_url)) {
    $relative_url = '/';
}

if (count($argv) == 1) {
    Command_Line::quit('Requirement argument not found');
}
// show usage information if user gave --help
if (($argv[1] == '--help') || ($argv[1] == 'help')) {
    Command_Line::usage(__FILE__);
}

$should_confirm = Command_Line::isSafeExecution();
$scheme = $port == 443 ? 'https' : 'http';
$client = new Eventum_RPC("$scheme://$hostname$relative_url/rpc/xmlrpc.php");
//$client->setCredentials($user_email, $user_password);
if (in_array('--debug', $argv)) {
    $client->setDebug(true);
    $debug = true;
    unset($argv[array_search('--debug', $argv)]);
} else {
    $debug = false;
}

$auth = array($user_email, $user_password);

$issue_id = (integer) $argv[1];
try {
    if ($issue_id > 0) {
        if (count($argv) == 2) {
            Command_Line::printIssueDetails($client, $auth, $issue_id);
        } else {
            if ($should_confirm) {
                Command_Line::promptConfirmation($client, $auth, $issue_id, @$argv);
            }
            switch ($argv[2]) {
                case 'assign':
                    if (count($argv) == 3) {
                        Command_Line::quit('Missing parameter for the developer');
                    }
                    Command_Line::assignIssue($client, $auth, $issue_id, $argv[3]);
                    break;
                case 'take':
                    Command_Line::takeIssue($client, $auth, $issue_id);
                    break;
                case 'add-replier':
                case 'ar':
                    // adds a user to the list of authorized repliers
                    if (count($argv) == 3) {
                        Command_Line::quit('Missing parameter for the developer');
                    }
                    Command_Line::addAuthorizedReplier($client, $auth, $issue_id, $argv[3]);
                    break;
                case 'set-status':
                case 'ss':
                    if (count($argv) == 3) {
                        Command_Line::quit('Missing parameter for the status');
                    }
                    Command_Line::setIssueStatus($client, $auth, $issue_id, $argv[3]);
                    break;
                case 'add-time':
                case 'at':
                    if (count($argv) == 3) {
                        Command_Line::quit('Missing parameter for time worked');
                    }
                    $check = (integer) $argv[3];
                    if ($check == 0) {
                        Command_Line::quit("Third argument to command 'add-time' should be a number");
                    }
                    Command_Line::addTimeEntry($client, $auth, $issue_id, $check);
                    break;
                case 'list-files':
                case 'lf':
                    Command_Line::printFileList($client, $auth, $issue_id);
                    break;
                case 'get-file':
                case 'gf':
                    if (count($argv) == 3) {
                        Command_Line::quit('Missing parameter for the file number');
                    }
                    Command_Line::getFile($client, $auth, $issue_id, $argv[3]);
                    break;
                case 'close':
                    Command_Line::closeIssue($client, $auth, $issue_id);
                    break;

                // email related commands
                case 'list-emails':
                case 'le':
                    // lists all emails for the given issue
                    Command_Line::listEmails($client, $auth, $issue_id);
                    break;
                case 'get-email':
                case 'ge':
                    // views an email
                    if (count($argv) == 3) {
                        Command_Line::quit('Missing parameter for the email number');
                    }
                    if (@$argv[4] == '--full') {
                        $full = true;
                    } else {
                        $full = false;
                    }
                    Command_Line::printEmail($client, $auth, $issue_id, $argv[3], $full);
                    break;

                // note related commands
                case 'list-notes':
                case 'ln':
                    // list notes for the given issues
                    Command_Line::listNotes($client, $auth, $issue_id);
                    break;
                case 'get-note':
                case 'gn':
                    // view a note
                    if (count($argv) == 3) {
                        Command_Line::quit('Missing parameter for the note number');
                    }
                    Command_Line::printNote($client, $auth, $issue_id, $argv[3]);
                    break;
                case 'convert-note':
                case 'cn':
                    // convert a note to an email
                    if (empty($argv[3])) {
                        Command_Line::quit('Missing parameter for the note number');
                    }
                    if (@$argv[4] != 'draft' && @$argv[4] != 'email') {
                        Command_Line::quit("4th parameter must be 'draft' or 'email'");
                    }
                    if (@$argv[5] == 'authorize') {
                        $authorize_sender = true;
                    } else {
                        $authorize_sender = false;
                    }
                    Command_Line::convertNote($client, $auth, $issue_id, $argv[3], $argv[4], $authorize_sender);
                    break;

                // draft related commands
                case 'list-drafts':
                case 'ld':
                    // list drafts
                    Command_Line::listDrafts($client, $auth, $issue_id);
                    break;
                case 'get-draft':
                case 'gd':
                    // viewing a draft
                    if (count($argv) == 3) {
                        Command_Line::quit('Missing parameter for the draft number');
                    }
                    Command_Line::printDraft($client, $auth, $issue_id, $argv[3]);
                    break;
                case 'send-draft':
                case 'sd':
                    // viewing a draft
                    if (count($argv) == 3) {
                        Command_Line::quit('Missing parameter for the draft number');
                    }
                    Command_Line::sendDraft($client, $auth, $issue_id, $argv[3]);
                    break;
                case '--full':
                    // Display Issue Details + Issue Custom Fields
                    Command_Line::printIssueDetails($client, $auth, $issue_id, true);
                    break;
                case 'custom-fields':
                case 'cf':
                    // display custom fields
                    Command_Line::printIssueCustomFields($client, $auth, $issue_id);
                    break;

                case 'redeem':
                    // marking an issue as redeemed
                    Command_Line::redeemIssue($client, $auth, $issue_id);
                    break;

                case 'unredeem':
                    // unmarks issue as redeemed incident
                    Command_Line::unredeemIssue($client, $auth, $issue_id);
                    break;

                default:
                    Command_Line::quit("Unknown command '" . $argv[2] . "'");
            }
        }
    } else {
        if ($argv[1] == 'developers') {
            Command_Line::printDeveloperList($client, $auth);
        } elseif ($argv[1] == 'open-issues') {
            if (count($argv) == 3) {
                if (@$argv[2] == 'my') {
                    $show_all_issues = false;
                    $status = '';
                } else {
                    $show_all_issues = true;
                    $status = $argv[2];
                }
            } elseif (count($argv) == 4) {
                if (@$argv[3] == 'my') {
                    $show_all_issues = false;
                } else {
                    $show_all_issues = true;
                }
                $status = $argv[2];
            } else {
                $show_all_issues = true;
                $status = '';
            }
            Command_Line::printOpenIssues($client, $auth, $show_all_issues, $status);
        } elseif ($argv[1] == 'list-status') {
            Command_Line::printStatusList($client, $auth);
        } elseif ($argv[1] == 'customer') {
            if (count($argv) != 4) {
                Command_Line::quit('Wrong parameter count');
            }
            Command_Line::lookupCustomer($client, $auth, $argv[2], $argv[3]);
        } elseif (($argv[1] == 'weekly-report') || ($argv[1] == 'wr')) {
            if (count(@$argv) >= 4 and $argv[3] != '--separate-closed') {
                $separate_closed = (@$argv[4] == '--separate-closed');
                // date range
                Command_Line::getWeeklyReport($client, $auth, 0, $argv[2], $argv[3], $separate_closed);
            } else {
                // weekly
                if (@$argv[2] == '') {
                    $separate_closed = false;
                    @$argv[2] = 0;
                } else {
                    $separate_closed = (@$argv[3] == '--separate-closed' or @$argv[2] == '--separate-closed');
                }
                Command_Line::getWeeklyReport($client, $auth, $argv[2], '', '', $separate_closed);
            }
        } elseif ($argv[1] == 'clock') {
            Command_Line::timeClock($client, $auth, @$argv[2]);
        } else {
            Command_Line::quit("Unknown parameter '" . $argv[1] . "'");
        }
    }
} catch (Eventum_RPC_Exception $e) {
    echo 'ERROR: ' . $e->getMessage() . "\n";
    if ($debug) {
        echo $e->getTraceAsString();
    }
}
