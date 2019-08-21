### Workflow Examples

The following are examples of individual workflow methods to perform special functionality. Note that these must all appear within an extended Abstract_Workflow_Backend class. Please refer to [Eventum:WorkflowDocumentation](WorkflowDocumentation.md) documentation for instructions on creating a file that extends the Abstract_Workflow_Backend class.

### Update Percentage Complete when Closing an Issue

    function handleIssueClosed($prj_id, $issue_id, $send_notification, $resolution_id, $status_id, $reason)
    {
        $sql = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                SET
                    iss_percent_complete = '100%'
                WHERE
                    iss_id = $issue_id";
        $res = $GLOBALS["db_api"]->dbh->query($sql);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        }
    }

### Allow all members of the notification list to send emails

    function canEmailIssue($prj_id, $issue_id, $email)
    {
        // get usr_id
        $usr_id = User::getUserIDByEmail($email);
        if (Notification::isSubscribedToEmails($issue_id, $email)) {
            return true;
        } else {
            return null;
        }
    }

### Allow anyone to reply to an email

This allows anyone to reply to an issue without first being added to the Authorized Repliers list, so they won't get blocked.

**WARNING:** This may not be desirable for all sites. Use a more restrictive method if problems result from bad senders.

    function canEmailIssue($prj_id, $issue_id, $email)
    {
        return true;
    }

### Add everyone on To/CC list of email to notification list

Note: If you are using email routing (or even if you are not, you might need to manually change \$address_not_too_add.

        function handleNewEmail($prj_id, $issue_id, $message, $row = FALSE, $closing = false)
        {
            $project_details = Project::getDetails(Issue::getProjectID($issue_id));
            $address_not_too_add = $project_details['prj_outgoing_sender_email'];

            if (!empty($row['to'])) {
                $to_addresses = Mail_API::getAddressInfo($row['to'], true);
                print_r($to_addresses);
                foreach ($to_addresses as $address) {
                    if ($address['email'] == $address_not_too_add) {
                        continue;
                    }
                    Notification::subscribeEmail(Auth::getUserID(), $issue_id, $address['email'], Notification::getDefaultActions());
                }
            }
            if (!empty($row['cc'])) {
                $cc_addresses = Mail_API::getAddressInfo($row['cc'], true);
                foreach ($cc_addresses as $address) {
                    if ($address['email'] == $address_not_too_add) {
                        continue;
                    }
                    Notification::subscribeEmail(Auth::getUserID(), $issue_id, $address['email'], Notification::getDefaultActions());
                }
            }
        }

### No external email

Stops Eventum from sending emails other than to registered users. Thanks to Bryan for writing this.

        function shouldEmailAddress($prj_id, $address)
        {
            $usr_id = User::getUserIDByEmail($address);
            if (empty($usr_id)) {
                return false;
            } else {
                return true;
            }
        }

### Allow all Developers to send email

Allow all Developer users to send email to the issue without being an assignee or on the authorized repliers list.

        /**
         * Allows all users with a role of developer or above to email
         *
         * @param   integer $prj_id The project ID.
         * @param   integer $issue_id The ID of the issue
         * @param   string The email address that is trying to send an email
         * @return  boolean true if the sender can email the issue, false if the sender
         *          should not email the issue and null if the default rules should be used.
         */
        function canEmailIssue($prj_id, $issue_id, $email)
        {
            // get usr_id
            $usr_id = User::getUserIDByEmail($email);
            if ((!empty($usr_id)) && (User::getRoleByUser($usr_id, $prj_id) >= User::getRoleID("Developer"))) {
                 return true;
            } else {
                 return null;
            }
        }

### Prevent certain email addresses from being added to NL

        function shouldEmailAddress($prj_id, $address)
        {
            $bad_emails = array('dont-email-this@example.com', 'bad-email@example.com');
            if (in_array(strtolower($address), $bad_emails)) {
                return false;
            } else {
                return true;
            }
        }

### Add anonymous reporter to issue notification list

Original code from [mailing list](http://lists.mysql.com/eventum-users/1711), but made it work.

Usage: Add two custom fields to anonymous reporting (name, email) and add a new workflow.

Notice: Remember to replace NameOfYourBackend with your Class backend name which extends Abstract_Workflow_Backend in your custom backend file.

    function handleNewIssue($prj_id, $issue_id, $has_TAM, $has_RR)
    {
     // Add submitter to the authorized sender list and notification list
     //$email = "testing@gmail.com";

     //I decided to keep this custom function in my workflow file, although it should belong to class.issue.php
     $email = NameOfYourBackend::getCustomFieldValue($issue_id,"2"); //"2" is the id of custom field "email"

     if (!empty($email)) {
      $add_history = true;
      $actions = array("updated","closed","files","emails");
      Authorized_Replier::manualInsert($issue_id, $email, $add_history);
      Notification::subscribeEmail('1', $issue_id, $email, $actions);
     }
    }

    function getCustomFieldValue($issue_id,$icf_fld_id)
    {
     $stmt = "SELECT
               icf_value
              FROM
               " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_custom_field
              WHERE
               icf_fld_id=" . Misc::escapeInteger($icf_fld_id) . "
              AND
               icf_iss_id=" . Misc::escapeInteger($issue_id) ."
              LIMIT 1";

     $res = $GLOBALS["db_api"]->dbh->getOne($stmt);

     if (PEAR::isError($res)) {
      Error_Handler::logError(array($res->getMessage(),
      $res->getDebugInfo()), __FILE__, __LINE__);
      return "";
     } else {
      return $res;
     }
    }

### Re-open a closed issue when new email is associated

Original code from [mailing list](http://lists.mysql.com/eventum-users/4693).

        function handleNewEmail($prj_id, $issue_id, $message, $row = false, $closing = false)
        {
            $current_status_id = Issue::getStatusID($issue_id);
            $closed_status_id = Status::getStatusID('killed'); // some closed context status
            if ((!empty($closed_status_id)) && ($current_status_id == $closed_status_id)) {
                $open_status_id = Status::getStatusID('discovery'); // some not closed context status
                Issue::setStatus($issue_id, $open_status_id);
            }
        }

WARNING: This code will change the status of an issue when an email is associated by ANY mean, not necessarily an auto-associated email. For example if you update the closed issue, send email or close the issue with Send Notification to All, it will add an email to the Associated Emails list and the status will be updated.

### Change issue status on incoming customer email

function handleNewEmail($prj_id, $issue_id, $message, $row = false, $closing = false)
    {
      if (!empty($row) && $row['customer_id']) {
        $waiting_on_support_id = Status::getStatusID('Waiting on Support');
       Issue::setStatus($issue_id, $waiting_on_support_id);
     }
   }
