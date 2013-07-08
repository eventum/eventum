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


// Constants used by customer class.
define("CUSTOMER_EXCLUDE_EXPIRED", 1);

class Customer
{
    /**
     * Returns the list of available customer backends by listing the class
     * files in the backend directory.
     *
     * @access  public
     * @return  array Associative array of filename => name
     */
    function getBackendList()
    {
        $files = Misc::getFileList(APP_INC_PATH . '/customer');
        $files = array_merge($files, Misc::getFileList(APP_LOCAL_PATH. '/customer'));
        $list = array();
        for ($i = 0; $i < count($files); $i++) {
            // make sure we only list the customer backends
            if (preg_match('/^class\.(.*)\.php$/', $files[$i], $matches)) {
                // display a prettyfied backend name in the admin section
                if ($matches[1] == "abstract_customer_backend") {
                    continue;
                }
                $name = ucwords(str_replace('_', ' ', $matches[1]));
                $list[$files[$i]] = $name;
            }
        }
        return $list;
    }


    /**
     * Returns the customer backend class file associated with the given
     * project ID.
     *
     * @param   integer $prj_id The project ID
     * @return  string The customer backend class filename
     */
    private static function _getBackendNameByProject($prj_id)
    {
        static $backends;

        if (isset($backends[$prj_id])) {
            return $backends[$prj_id];
        }

        $stmt = "SELECT
                    prj_id,
                    prj_customer_backend
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project
                 ORDER BY
                    prj_id";
        $res = DB_Helper::getInstance()->getAssoc($stmt);
        if (PEAR::isError($res)) {
	        /** @var $res PEAR_Error */
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return '';
        } else {
            $backends = $res;
            return @$backends[$prj_id];
        }
    }


    /**
     * Includes the appropriate customer backend class associated with the
     * given project ID, instantiates it and returns the class.
     *
     * @param   integer $prj_id The project ID
     * @return  Abstract_Customer_Backend
     */
    public static function &_getBackend($prj_id)
    {
        static $setup_backends;

        if (empty($setup_backends[$prj_id])) {
            $backend_class = self::_getBackendNameByProject($prj_id);
            if (empty($backend_class)) {
                $setup_backends[$prj_id] = false;
            } else {
                $file_name_chunks = explode(".", $backend_class);
                $class_name = $file_name_chunks[1] . "_Customer_Backend";


                if (file_exists(APP_LOCAL_PATH . "/customer/$backend_class")) {
                    require_once(APP_LOCAL_PATH . "/customer/$backend_class");
                } else {
                    require_once APP_INC_PATH . "/customer/$backend_class";
                }

                $setup_backends[$prj_id] = new $class_name;
                $setup_backends[$prj_id]->connect();
            }
        }
        return $setup_backends[$prj_id];
    }


    /**
     * Checks whether the given project ID is setup to use customer integration
     * or not.
     *
     * @param   integer $prj_id The project ID
     * @return  boolean
     */
    public static function hasCustomerIntegration($prj_id)
    {
        $backend = self::_getBackendNameByProject($prj_id);
        if (empty($backend)) {
            return false;
        } else {
            return true;
        }
    }


    // XXX: put documentation here
    public static function getBackendImplementationName($prj_id)
    {
        if (!self::hasCustomerIntegration($prj_id)) {
            return '';
        }
        $backend =& self::_getBackend($prj_id);
        return $backend->getName();
    }


    /**
     * Returns true if the backend uses support levels, false otherwise
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @return  boolean True if the project uses support levels.
     */
    function doesBackendUseSupportLevels($prj_id)
    {
        $backend =& self::_getBackend($prj_id);
        if ($backend === FALSE) {
            return false;
        } else {
            return $backend->usesSupportLevels();
        }
    }


    /**
     * Returns the contract status associated with the given customer ID.
     * Possible return values are 'active', 'in_grace_period' and 'expired'.
     *
     * @param   integer $prj_id The project ID
     * @param   integer $customer_id The customer ID
     * @param   integer $contract_id The contract ID
     * @return  string The contract status
     */
    public static function getContractStatus($prj_id, $customer_id, $contract_id = false)
    {
        $backend =& self::_getBackend($prj_id);
        return $backend->getContractStatus($customer_id, $contract_id);
    }



    /**
     * Retrieves the customer titles associated with the given list of issues.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @param   array $result The list of issues
     * @see     Search::getListing()
     */
    function getCustomerTitlesByIssues($prj_id, &$result)
    {
        $backend =& self::_getBackend($prj_id);
        $backend->getCustomerTitlesByIssues($result);
    }



    /**
     * Retrieves the support levels associated with the given list of issues.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @param   array $result The list of issues
     * @see     Search::getListing()
     */
    function getSupportLevelsByIssues($prj_id, &$result)
    {
        $backend =& self::_getBackend($prj_id);
        $backend->getSupportLevelsByIssues($result);
    }


    /**
     * Method used to get the details of the given customer.
     *
     * @param   integer $prj_id The project ID
     * @param   integer $customer_id The customer ID
     * @param   boolean $force_refresh If the cache should not be used.
     * @param   bool|int $contract_id The contract ID
     * @param   bool|int $contact_id
     * @return  array The customer details
     */
    public static function getDetails($prj_id, $customer_id, $force_refresh = false, $contract_id = false,
                                      $contact_id = false)
    {
        $backend =& self::_getBackend($prj_id);
        return $backend->getDetails($customer_id, $force_refresh, $contract_id, $contact_id);
    }


    /**
     * Returns true if this issue has been counted a valid incident
     *
     * @see /docs/Customer_API.html
     * @access  public
     * @param   integer $prj_id The project ID
     * @param   integer $issue_id The ID of the issue
     * @param   integer $incident_type The type of incident
     * @return  boolean True if this is a redeemed incident.
     */
    function isRedeemedIncident($prj_id, $issue_id, $incident_type = false)
    {
        $backend =& self::_getBackend($prj_id);
        return $backend->isRedeemedIncident($issue_id, $incident_type);
    }


    /**
     * Returns an array of the curently redeemed incident types for the issue.
     *
     * @see /docs/Customer_API.html
     * @access  public
     * @param   integer $prj_id The project ID
     * @return  array An array containing the redeemed incident types
     */
    function getRedeemedIncidentDetails($prj_id, $issue_id)
    {
        $types = self::getIncidentTypes($prj_id);
        $data = array();
        foreach ($types as $id => $title) {
            if (self::isRedeemedIncident($prj_id, $issue_id, $id)) {
                $data[$id] = array(
                    'title' =>  $title,
                    'is_redeemed'   =>  1
                );
            }
        }
        return $data;
    }


    /**
     * Updates the incident counts
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @param   integer $issue_id The issue ID
     * @param   array $data An array of data containing which incident types to update.
     * @return  integer 1 if all updates were successful, -1 or -2 otherwise.
     */
    function updateRedeemedIncidents($prj_id, $issue_id, $data)
    {
        $details = self::getDetails($prj_id, Issue::getCustomerID($issue_id), Issue::getContractID($issue_id));
        foreach ($details['incident_details'] as $type_id => $type_details) {
            $is_redeemed = self::isRedeemedIncident($prj_id, $issue_id, $type_id);
            if (($is_redeemed) && (@$data[$type_id] != 1)) {
                // un-redeem issue
                $res = self::unflagIncident($prj_id, $issue_id, $type_id);
            } elseif ((!$is_redeemed) && (@$data[$type_id] == 1)) {
                // redeem issue
                if (($type_details['total'] - $type_details['redeemed']) > 0) {
                    $res = self::flagIncident($prj_id, $issue_id, $type_id);
                } else {
                    $res = -1;
                }
            } else {
                $res = 1;
            }
            if ($res != 1) {
                return $res;
            }
        }
        return $res;
    }


    /**
     * Marks an issue as a redeemed incident.
     * @see /docs/Customer_API.html
     * @param   integer $prj_id The project ID
     * @param   integer $issue_id The ID of the issue
     * @param   integer $incident_type The type of incident
     */
    public static function flagIncident($prj_id, $issue_id, $incident_type)
    {
        $backend =& self::_getBackend($prj_id);
        return $backend->flagIncident($issue_id, $incident_type);
    }


    /**
     * Marks an issue as not a redeemed incident.
     *
     * @see /docs/Customer_API.html
     * @access  public
     * @param   integer $prj_id The project ID
     * @param   integer $issue_id The ID of the issue
     * @param   integer $incident_type The type of incident
     */
    function unflagIncident($prj_id, $issue_id, $incident_type)
    {
        $backend =& self::_getBackend($prj_id);
        return $backend->unflagIncident($issue_id, $incident_type);
    }


    /**
     * Checks whether the active per-incident contract associated with the given
     * customer ID has any incidents available to be redeemed.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @param   integer $customer_id The customer ID
     * @param   integer $incident_type The type of incident
     * @return  boolean
     */
    function hasIncidentsLeft($prj_id, $customer_id, $incident_type = false)
    {
        $backend =& self::_getBackend($prj_id);
        return $backend->hasIncidentsLeft($customer_id, $incident_type);
    }


    /**
     * Checks whether the active contract associated with the given customer ID
     * is a per-incident contract or not.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @param   integer $customer_id The customer ID
     * @return  boolean
     */
    function hasPerIncidentContract($prj_id, $customer_id)
    {
        $backend =& self::_getBackend($prj_id);
        return $backend->hasPerIncidentContract($customer_id);
    }


    /**
     * Returns the total number of allowed incidents for the given support
     * contract ID.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @param   integer $support_no The support contract ID
     * @param   integer $incident_type The type of incident
     * @return  integer The total number of incidents
     */
    function getTotalIncidents($prj_id, $support_no, $incident_type)
    {
        $backend =& self::_getBackend($prj_id);
        return $backend->getTotalIncidents($support_no, $incident_type);
    }


    /**
     * Returns the number of incidents remaining for the given support
     * contract ID.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @param   integer $support_no The support contract ID
     * @param   integer $incident_type The type of incident
     * @return  integer The number of incidents remaining.
     */
    function getIncidentsRemaining($prj_id, $support_no, $incident_type)
    {
        $backend =& self::_getBackend($prj_id);
        return $backend->getIncidentsRemaining($support_no, $incident_type);
    }


    /**
     * Returns the incident types available.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @return  array An array of per incident types
     */
    function getIncidentTypes($prj_id)
    {
        $backend =& self::_getBackend($prj_id);
        return $backend->getIncidentTypes();
    }


    /**
     * Method used to send a notice that the per-incident limit being reached.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @param   integer $contact_id The customer contact ID
     * @param   integer $customer_id The customer ID
     * @param   boolean $new_issue If the customer just tried to create a new issue.
     * @return  void
     */
    function sendIncidentLimitNotice($prj_id, $contact_id, $customer_id, $new_issue = false)
    {
        $backend =& self::_getBackend($prj_id);
        return $backend->sendIncidentLimitNotice($contact_id, $customer_id, $new_issue);
    }


    /**
     * Returns a list of customers (companies) in the customer database.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @return  array An associated array of customers.
     */
    function getAssocList($prj_id)
    {
        $backend =& self::_getBackend($prj_id);
        return $backend->getAssocList();
    }


    /**
     * Method used to get the customer names for the given customer id.
     *
     * @param   integer $customer_id The customer ID
     * @return  string The customer name
     */
    public static function getTitle($prj_id, $customer_id)
    {
        $backend =& self::_getBackend($prj_id);
        if ($backend === FALSE) {
            return '';
        } else {
            return $backend->getTitle($customer_id);
        }
    }


    /**
     * Method used to get an associative array of the customer names
     * for the given list of customer ids.
     *
     * @access  public
     * @param   array $customer_ids The list of customers
     * @return  array The associative array of customer id => customer name
     */
    function getTitles($prj_id, $customer_ids)
    {
	    // TODO: this or backend prototype mismatches
        $backend =& self::_getBackend($prj_id);
        return $backend->getTitles($customer_ids);
    }


    /**
     * Method used to get the list of email addresses associated with the
     * contacts of a given customer.
     *
     * @access  public
     * @param   integer $customer_id The customer ID
     * @return  array The list of email addresses
     */
    function getContactEmailAssocList($prj_id, $customer_id)
    {
        $backend =& self::_getBackend($prj_id);
        return $backend->getContactEmailAssocList($customer_id);
    }


    /**
     * Method used to get the customer and customer contact IDs associated
     * with a given list of email addresses.
     *
     * @access  public
     * @param   array $emails The list of email addresses
     * @return  array The customer and customer contact ID
     */
    function getCustomerIDByEmails($prj_id, $emails)
    {
        $backend =& self::_getBackend($prj_id);
        return $backend->getCustomerIDByEmails($emails);
    }


    /**
     * Method used to get the overall statistics of issues in the system for a
     * given customer.
     *
     * @access  public
     * @param   integer $customer_id The customer ID
     * @return  array The customer related issue statistics
     */
    function getOverallStats($prj_id, $customer_id)
    {
        $backend =& self::_getBackend($prj_id);
        return $backend->getOverallStats($customer_id);
    }


    /**
     * Method used to build the overall customer profile from the information
     * stored in the customer database.
     *
     * @access  public
     * @param   integer $usr_id The Eventum user ID
     * @return  array The customer profile information
     */
    function getProfile($prj_id, $usr_id)
    {
        $backend =& self::_getBackend($prj_id);
        return $backend->getProfile($usr_id);
    }


    /**
     * Method used to get the contract details for a given customer contact.
     *
     * @access  public
     * @param   integer $contact_id The customer contact ID
     * @return  array The customer contract details
     */
    function getContractDetails($prj_id, $contact_id, $restrict_expiration = TRUE)
    {
        $backend =& self::_getBackend($prj_id);
        return $backend->getContractDetails($contact_id, $restrict_expiration);
    }


    /**
     * Method used to get the details associated with a customer contact.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @param   integer $contact_id The customer contact ID
     * @return  array The contact details
     */
    function getContactDetails($prj_id, $contact_id)
    {
        $backend =& self::_getBackend($prj_id);
        return $backend->getContactDetails($contact_id);
    }


    /**
     * Returns the list of customer IDs that are associated with the given
     * email value (wildcards welcome).
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @param   string $email The email value
     * @return  array The list of customer IDs
     */
    function getCustomerIDsLikeEmail($prj_id, $email)
    {
        $backend =& self::_getBackend($prj_id);
        return $backend->getCustomerIDsLikeEmail($email);
    }


    /**
     * Method used to notify the customer contact that an existing issue
     * associated with him was just marked as closed.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @param   integer $issue_id The issue ID
     * @param   integer $contact_id The customer contact ID
     * @param   boolean $send_notification Whether to send a notification about this action or not
     * @param   integer $resolution_id The resolution ID
     * @param   integer $status_id The status ID
     * @param   string $reason The reason for closing this issue
     * @return  void
     */
    function notifyIssueClosed($prj_id, $issue_id, $contact_id, $send_notification, $resolution_id, $status_id, $reason)
    {
        $backend =& self::_getBackend($prj_id);
        return $backend->notifyIssueClosed($issue_id, $contact_id, $send_notification, $resolution_id, $status_id, $reason);
    }


    /**
     * Performs a customer lookup and returns the matches, if
     * appropriate.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @param   string $field The field that we are trying to search against
     * @param   string $value The value that we are searching for
     * @param   array  $options An array of options for search
     * @return  array The list of customers
     */
    function lookup($prj_id, $field, $value, $options=array())
    {
        $backend =& self::_getBackend($prj_id);
        return $backend->lookup($field, $value, $options);
    }


    /**
     * Method used to notify the customer contact that a new issue was just
     * created and associated with his Eventum user.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @param   integer $issue_id The issue ID
     * @param   integer $contact_id The customer contact ID
     * @return  void
     */
    function notifyCustomerIssue($prj_id, $issue_id, $contact_id)
    {
        $backend =& self::_getBackend($prj_id);
        return $backend->notifyCustomerIssue($issue_id, $contact_id);
    }


    /**
     * Method used to get the list of available support levels.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @return  array The list of available support levels
     */
    function getSupportLevelAssocList($prj_id)
    {
        $backend =& self::_getBackend($prj_id);
        return $backend->getSupportLevelAssocList();
    }


    /**
     * Returns the support level of the current support contract for a given
     * customer ID.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @param   integer $customer_id The customer ID
     * @param   integer $contract_id The contract ID
     * @return  string The support contract level
     */
    function getSupportLevelID($prj_id, $customer_id, $contract_id = false)
    {
        $backend =& self::_getBackend($prj_id);
        return $backend->getSupportLevelID($customer_id, $contract_id);
    }


    /**
     * Returns the list of customer IDs for a given support contract level.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @param   integer $support_level_id The support level ID
     * @param   mixed $support_options An integer or array of integers indicating various options to get customers with.
     * @return  array The list of customer IDs
     */
    function getListBySupportLevel($prj_id, $support_level_id, $support_options = false)
    {
        $backend =& self::_getBackend($prj_id);
        return $backend->getListBySupportLevel($support_level_id, $support_options);
    }


    /**
     * Returns an array of support levels grouped together.
     *
     * @param   integer $prj_id The project ID
     * @return  array an array of support levels.
     */
    public static function getGroupedSupportLevels($prj_id)
    {
        $backend =& self::_getBackend($prj_id);
        return $backend->getGroupedSupportLevels($prj_id);
    }


    /**
     * Method used to send an expiration notice.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @param   integer $contact_id The customer contact ID
     * @param   boolean $is_expired Whether this customer is expired or not
     * @param   string  $contract_id The contract ID
     * @return  void
     */
    function sendExpirationNotice($prj_id, $contact_id, $is_expired = FALSE, $contract_id = false)
    {
        $backend =& self::_getBackend($prj_id);
        return $backend->sendExpirationNotice($contact_id, $is_expired, $contract_id);
    }


    /**
     * Checks whether the given technical contact ID is allowed in the current
     * support contract or not.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @param   integer $customer_contact_id The customer technical contact ID
     * @return  boolean
     */
    function isAllowedSupportContact($prj_id, $customer_contact_id)
    {
        $backend =& self::_getBackend($prj_id);
        return $backend->isAllowedSupportContact($customer_contact_id);
    }


    /**
     * Method used to get the associated customer and customer contact from
     * a given set of support emails. This is especially useful to automatically
     * associate an issue to the appropriate customer contact that sent a
     * support email.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @param   array $sup_ids The list of support email IDs
     * @return  array The customer and customer contact ID
     */
    function getCustomerInfoFromEmails($prj_id, $sup_ids)
    {
        $backend =& self::_getBackend($prj_id);
        return $backend->getCustomerInfoFromEmails($sup_ids);
    }


    /**
     * Method used to send an email notification to the sender of a
     * set of email messages that were manually converted into an
     * issue.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @param   integer $issue_id The issue ID
     * @param   array $sup_ids The email IDs
     * @param   integer $customer_id The customer ID
     * @return  array The list of recipient emails
     */
    function notifyEmailConvertedIntoIssue($prj_id, $issue_id, $sup_ids, $customer_id = FALSE)
    {
        $backend =& self::_getBackend($prj_id);
        return $backend->notifyEmailConvertedIntoIssue($issue_id, $sup_ids, $customer_id);
    }


    /**
     * Method used to send an email notification to the sender of an
     * email message that was automatically converted into an issue.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @param   integer $issue_id The issue ID
     * @param   string $sender The sender of the email message (and the recipient of this notification)
     * @param   string $date The arrival date of the email message
     * @param   string $subject The subject line of the email message
     * @return  void
     */
    function notifyAutoCreatedIssue($prj_id, $issue_id, $sender, $date, $subject)
    {
        $backend =& self::_getBackend($prj_id);
        return $backend->notifyAutoCreatedIssue($issue_id, $sender, $date, $subject);
    }


    /**
     * Method used to get the customer login grace period (number of days).
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @return  integer The customer login grace period
     */
    function getExpirationOffset($prj_id)
    {
        $backend =& self::_getBackend($prj_id);
        return $backend->_getExpirationOffset();
    }


    /**
     * Method used to get the details of the given customer contact.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @param   integer $contact_id The customer contact ID
     * @return  array The customer details
     */
    function getContactLoginDetails($prj_id, $contact_id)
    {
        $backend =& self::_getBackend($prj_id);
        return $backend->getContactLoginDetails($contact_id);
    }


    /**
     * Returns the end date of the current support contract for a given
     * customer ID.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @param   integer $customer_id The customer ID
     * @param   integer $contract_id The contract ID
     * @return  string The support contract end date
     */
    function getContractEndDate($prj_id, $customer_id, $contract_id = false)
    {
        $backend =& self::_getBackend($prj_id);
        return $backend->getContractEndDate($customer_id, $contract_id);
    }


    /**
     * Returns the name and email of the sales account manager of the given customer ID.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @param   integer $customer_id The customer ID
     * @return  array An array containing the name and email of the sales account manager
     */
    function getSalesAccountManager($prj_id, $customer_id)
    {
        $backend =& self::_getBackend($prj_id);
        return $backend->getSalesAccountManager($customer_id);
    }


    /**
     * Returns the start date of the current support contract for a given
     * customer ID.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @param   integer $customer_id The customer ID
     * @param   integer $contract_id The contract ID
     * @return  string The support contract start date
     */
    function getContractStartDate($prj_id, $customer_id, $contract_id = false)
    {
        $backend =& self::_getBackend($prj_id);
        return $backend->getContractStartDate($customer_id, $contract_id);
    }


    /**
     * Returns a message to be displayed to a customer on the top of the issue creation page.
     *
     * @param   integer $prj_id The project ID
     * @param   array $customer_id Customer ID.
     */
    function getNewIssueMessage($prj_id, $customer_id)
    {
        $backend =& self::_getBackend($prj_id);
        return $backend->getNewIssueMessage($customer_id);
    }


    /**
     * Return what business hours a customer falls into. Mainly used for international
     * customers.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @param   integer $customer_id The customer ID
     * @return  string The business hours
     */
    function getBusinessHours($prj_id, $customer_id)
    {
        $backend =& self::_getBackend($prj_id);
        return $backend->getBusinessHours($customer_id);
    }


    /**
     * Checks whether the given customer has a support contract that
     * enforces limits for the minimum first response time or not.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @param   integer $customer_id The customer ID
     * @param   integer $contract_id The contract ID
     * @return  boolean
     */
    function hasMinimumResponseTime($prj_id, $customer_id, $contract_id = false)
    {
        $backend =& self::_getBackend($prj_id);
        return $backend->hasMinimumResponseTime($customer_id, $contract_id);
    }


    /**
     * Returns the minimum first response time in seconds for the
     * support level associated with the given customer.
     *
     * @access  public
     * @param   integer $customer_id The customer ID
     * @param   integer $contract_id The contract ID
     * @return  integer The minimum first response time
     */
    function getMinimumResponseTime($prj_id, $customer_id, $contract_id = false)
    {
        $backend =& self::_getBackend($prj_id);
        return $backend->getMinimumResponseTime($customer_id, $contract_id);
    }


    /**
     * Returns the maximum first response time associated with the
     * support contract of the given customer.
     *
     * @access  public
     * @param   integer $customer_id The customer ID
     * @param   integer $contract_id The contract ID
     * @return  integer The maximum first response time, in seconds
     */
    function getMaximumFirstResponseTime($prj_id, $customer_id, $contract_id = false)
    {
        $backend =& self::_getBackend($prj_id);
        return $backend->getMaximumFirstResponseTime($customer_id, $contract_id);
    }


    /**
     * Returns the path php files for this customer backend are stored in.
     *
     * @param   integer $prj_id The project ID
     */
    function getPath($prj_id)
    {
        $backend =& self::_getBackend($prj_id);
        if (method_exists($backend, 'getPath')) {
            return $backend->getPath();
        } else {
            return 'customer/' . $backend->getName() . '/';
        }
    }


    /**
     * Returns the path templates for this customer backend are stored in.
     *
     * @param   integer $prj_id The project ID
     */
    function getTemplatePath($prj_id)
    {
        $backend =& self::_getBackend($prj_id);
        if (method_exists($backend, 'getTemplatePath')) {
            return $backend->getTemplatePath();
        } else {
            return 'customer/' . Customer::getBackendImplementationName($prj_id) . '/';
        }
    }


    /**
     * Returns if the specified customer / contract has the specified feature
     *
     * @param   integer $prj_id The project ID
     * @param   string  $customer_id
     * @param   string  $contract_id
     * @param   string  $feature
     * @return  boolean True if the contract has the feature, false otherwise.
     */
    function hasFeature($prj_id, $customer_id, $contract_id, $feature)
    {
        $backend =& self::_getBackend($prj_id);
        if (method_exists($backend, 'hasFeature')) {
            return $backend->hasFeature($customer_id, $contract_id, $feature);
        } else {
            return false;
        }
    }


    /**
     * Performs needed checks to see if a contact can login. Performs some default
     * checks if the backend does not implement checks
     *
     * @param   integer $prj_id
     * @param   integer $customer_id
     * @param   integer $contact_id
     */
    function authenticateCustomer($prj_id, $customer_id, $contact_id)
    {
        $tpl = new Template_Helper();
        $backend =& self::_getBackend($prj_id);
        if (method_exists($backend, 'authenticateCustomer')) {
            $backend->authenticateCustomer($customer_id, $contact_id);
        } else {
            // check if customer is expired
            $usr_id = Auth::getUserID();
            $contact_id = User::getCustomerContactID($usr_id);
            if ((!empty($contact_id)) && ($contact_id != -1)) {
                $status = self::getContractStatus($prj_id, User::getCustomerID($usr_id));
                $email = User::getEmailByContactID($contact_id);
                if ($status == 'expired') {
                    self::sendExpirationNotice($prj_id, $contact_id, true);
                    Auth::saveLoginAttempt($email, 'failure', 'expired contract');

                    Auth::removeCookie(APP_PROJECT_COOKIE);

                    $contact_id = User::getCustomerContactID($usr_id);
                    $tpl->setTemplate(Customer::getTemplatePath($prj_id) . "/customer_expired.tpl.html");
                    $tpl->assign('customer', self::getContractDetails($prj_id, $contact_id, false));
                    $tpl->displayTemplate();
                    exit;
                } elseif ($status == 'in_grace_period') {
                    self::sendExpirationNotice($prj_id, $contact_id);
                    $tpl->setTemplate(Customer::getTemplatePath($prj_id) . "/grace_period.tpl.html");
                    $tpl->assign('customer', self::getContractDetails($prj_id, $contact_id, false));
                    $tpl->assign('expiration_offset', self::getExpirationOffset($prj_id));
                    $tpl->displayTemplate();
                    exit;
                }
                // check with cnt_support to see if this contact is allowed in this support contract
                if (!self::isAllowedSupportContact($prj_id, $contact_id)) {
                    Auth::saveLoginAttempt($email, 'failure', 'not allowed as technical contact');
                    Auth::removeCookie(APP_COOKIE);
                    Auth::redirect("index.php?err=4&email=" . $email);
                }
            }
        }
    }

    /**
     * Method used to get the list of technical account managers
     * currently available in the system.
     *
     * @access  public
     * @return  array The list of account managers
     */
    function getAccountManagerList()
    {
        $stmt = "SELECT
                    cam_id,
                    cam_prj_id,
                    cam_customer_id,
                    cam_type,
                    usr_full_name
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "customer_account_manager,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
                 WHERE
                    cam_usr_id=usr_id";
        $res = DB_Helper::getInstance()->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
	        /** @var $res PEAR_Error */
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            for ($i = 0; $i < count($res); $i++) {
                $res[$i]['customer_title'] = self::getTitle($res[$i]['cam_prj_id'], $res[$i]['cam_customer_id']);
            }
            return $res;
        }
    }


    /**
     * Method used to add a new association of Eventum user =>
     * customer ID. This association will provide the basis for a
     * new role of technical account manager in Eventum.
     *
     * @access  public
     * @return  integer 1 if the insert worked properly, any other value otherwise
     */
    function insertAccountManager()
    {
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "customer_account_manager
                 (
                    cam_prj_id,
                    cam_customer_id,
                    cam_usr_id,
                    cam_type
                 ) VALUES (
                    " . Misc::escapeInteger($_POST['project']) . ",
                    " . Misc::escapeInteger($_POST['customer']) . ",
                    " . Misc::escapeInteger($_POST['manager']) . ",
                    '" . Misc::escapeString($_POST['type']) . "'
                 )";
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            return 1;
        }
    }


    /**
     * Method used to get the details of a given account manager.
     *
     * @access  public
     * @param   integer $cam_id The account manager ID
     * @return  array The account manager details
     */
    function getAccountManagerDetails($cam_id)
    {
        $stmt = "SELECT
                    *
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "customer_account_manager
                 WHERE
                    cam_id=" . Misc::escapeInteger($cam_id);
        $res = DB_Helper::getInstance()->getRow($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        } else {
            return $res;
        }
    }


    /**
     * Method used to update the details of an account manager.
     *
     * @access  public
     * @return  integer 1 if the update worked properly, any other value otherwise
     */
    function updateAccountManager()
    {
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "customer_account_manager
                 SET
                    cam_prj_id=" . Misc::escapeInteger($_POST['project']) . ",
                    cam_customer_id='" . Misc::escapeString($_POST['customer']) . "',
                    cam_usr_id=" . Misc::escapeInteger($_POST['manager']) . ",
                    cam_type='" . Misc::escapeString($_POST['type']) . "'
                 WHERE
                    cam_id=" . $_POST['id'];
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            return 1;
        }
    }


    /**
     * Method used to remove a technical account manager from the
     * system.
     *
     * @access  public
     * @return  boolean
     */
    function removeAccountManager()
    {
        $items = @implode(", ", Misc::escapeInteger($_POST["items"]));
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "customer_account_manager
                 WHERE
                    cam_id IN ($items)";
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            return true;
        }
    }


    /**
     * Method used to get the list of technical account managers for
     * a given customer ID.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @param   integer $customer_id The customer ID
     * @return  array The list of account managers
     */
    function getAccountManagers($prj_id, $customer_id)
    {
        $stmt = "SELECT
                    cam_usr_id,
                    usr_email,
                    cam_type
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "customer_account_manager,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
                 WHERE
                    cam_usr_id=usr_id AND
                    cam_prj_id=" . Misc::escapeInteger($prj_id) . " AND
                    cam_customer_id='" . Misc::escapeString($customer_id) . "'";
        $res = DB_Helper::getInstance()->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        } else {
            if (empty($res)) {
                return array();
            } else {
                return $res;
            }
        }
    }


    /**
     * Returns any notes for for the specified customer.
     *
     * @access  public
     * @param   integer $customer_id The customer ID
     * @return  array An array containg the note details.
     */
    function getNoteDetailsByCustomer($customer_id)
    {
        $stmt = "SELECT
                    cno_id,
                    cno_prj_id,
                    cno_customer_id,
                    cno_note
                FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "customer_note
                WHERE
                    cno_customer_id = '" . Misc::escapeString($customer_id) . "'";
        $res = DB_Helper::getInstance()->getRow($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        } else {
            return $res;
        }
    }


    /**
     * Returns any note details for for the specified id.
     *
     * @access  public
     * @param   integer $customer_id The customer ID
     * @return  array An array containg the note details.
     */
    function getNoteDetailsByID($cno_id)
    {
        $stmt = "SELECT
                    cno_prj_id,
                    cno_customer_id,
                    cno_note
                FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "customer_note
                WHERE
                    cno_id = " . Misc::escapeInteger($cno_id);
        $res = DB_Helper::getInstance()->getRow($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        } else {
            return $res;
        }
    }


    /**
     * Returns an array of notes for all customers.
     *
     * @access  public
     * @return  array An array of notes.
     */
    function getNoteList()
    {
        $stmt = "SELECT
                    cno_id,
                    cno_prj_id,
                    cno_customer_id,
                    cno_note
                FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "customer_note
                ORDER BY
                    cno_customer_id ASC";
        $res = DB_Helper::getInstance()->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        } else {
            for ($i = 0; $i < count($res); $i++) {
                $res[$i]['customer_title'] = self::getTitle($res[$i]['cno_prj_id'], $res[$i]['cno_customer_id']);
            }
            return $res;
        }
    }


    /**
     * Updates a note.
     *
     * @access  public
     * @param   integer $cno_id The id of this note.
     * @param   integer $prj_id The project ID
     * @param   integer $customer_id The id of the customer.
     * @param   string $note The text of this note.
     */
    function updateNote($cno_id, $prj_id, $customer_id, $note)
    {
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "customer_note
                 SET
                    cno_note='" . Misc::escapeString($note) . "',
                    cno_prj_id=" . Misc::escapeInteger($prj_id) . ",
                    cno_customer_id='" . Misc::escapeString($customer_id) . "',
                    cno_updated_date='" . Date_Helper::getCurrentDateGMT() . "'
                 WHERE
                    cno_id=" . Misc::escapeInteger($cno_id);
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            return 1;
        }
    }


    /**
     * Adds a quick note for the specified customer.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @param   integer $customer_id The id of the customer.
     * @param   string  $note The note to add.
     */
    function insertNote($prj_id, $customer_id, $note)
    {
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "customer_note
                 (
                    cno_prj_id,
                    cno_customer_id,
                    cno_created_date,
                    cno_updated_date,
                    cno_note
                 ) VALUES (
                    " . Misc::escapeInteger($prj_id) . ",
                    " . Misc::escapeInteger($customer_id) . ",
                    '" . Date_Helper::getCurrentDateGMT() . "',
                    '" . Date_Helper::getCurrentDateGMT() . "',
                    '" . Misc::escapeString($note) . "'
                 )";
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            return 1;
        }
    }


    /**
     * Removes the selected notes from the database.
     *
     * @access  public
     * @param   array $ids An array of cno_id's to be deleted.
     */
    function removeNotes($ids)
    {
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "customer_note
                 WHERE
                    cno_id IN (" . join(", ", Misc::escapeInteger($ids)) . ")";
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            return 1;
        }
    }

}
