<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
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
// | Authors: Bryan Alsdorf <bryan@mysql.com>                             |
// +----------------------------------------------------------------------+
//

/**
 * Abstract class that all customer backends should extend. This is so any new
 * customer methods added in future releases won't break existing backends.
 *
 * @author Bryan Alsdorf <bryan@mysql.com>
 */
class Abstract_Customer_Backend
{

    /**
     * Return what business hours a customer falls into. Mainly used for international
     * customers.
     *
     * @access  public
     * @param   integer $customer_id The customer ID
     * @return  string The business hours
     */
    function getBusinessHours($customer_id)
    {
    }


    /**
     * Returns a message to be displayed to a customer on the top of the issue creation page.
     *
     * @param   array $customer_id Customer ID.
     */
    function getNewIssueMessage($customer_id)
    {
    }


    /**
     * Checks whether the given customer has a support contract that
     * enforces limits for the minimum first response time or not.
     *
     * @access  public
     * @param   integer $customer_id The customer ID
     * @param   integer $contract_id The contract ID
     * @return  boolean
     */
    function hasMinimumResponseTime($customer_id, $contract_id = false)
    {
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
    function getMinimumResponseTime($customer_id, $contract_id = false)
    {
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
    function getMaximumFirstResponseTime($customer_id, $contract_id = false)
    {
    }


    /**
     * Returns an array of incident types
     *
     * @return  array An array of incident types.
     */
    function getIncidentTypes()
    {
        return array();
    }


    /**
     * Returns true if the backend uses support levels, false otherwise
     *
     * @access  public
     * @return  boolean True if the project uses support levels.
     */
    function usesSupportLevels()
    {
        return false;
    }


    /**
     * Connect to the customer database
     *
     * @access  public
     */
    function connect()
    {
    }


    /**
     * Returns the contract status associated with the given customer ID.
     * Possible return values are 'active', 'in_grace_period' and 'expired'.
     *
     * @access  public
     * @param   integer $customer_id The customer ID
     * @param   integer $contract_id The contract ID
     * @return  string The contract status
     */
    function getContractStatus($customer_id, $contract_id = false)
    {
    }


    /**
     * Retrieves the customer titles associated with the given list of issues.
     *
     * @access  public
     * @param   array $result The list of issues
     * @see     Issue::getListing()
     */
    function getCustomerTitlesByIssues(&$result)
    {
    }


    /**
     * Method used to get the details of the given customer.
     *
     * @access  public
     * @param   integer $customer_id The customer ID
     * @return  array The customer details
     */
    function getDetails($customer_id)
    {
    }


    /**
     * Returns true if this issue has been counted a valid incident
     *
     * @see /docs/Customer_API.html
     * @access  public
     * @param   integer $issue_id The ID of the issue
     * @return  boolean True if this is a redeemed incident.
     */
    function isRedeemedIncident($issue_id)
    {
    }


    /**
     * Marks an issue as a redeemed incident.
     *
     * @see /docs/Customer_API.html
     * @access  public
     * @param   integer $issue_id The ID of the issue
     */
    function flagIncident($issue_id)
    {
    }


    /**
     * Marks an issue as not a redeemed incident.
     *
     * @see /docs/Customer_API.html
     * @access  public
     * @param   integer $issue_id The ID of the issue
     */
    function unflagIncident($issue_id)
    {
    }


    /**
     * Checks whether the active per-incident contract associated with the given
     * customer ID has any incidents available to be redeemed.
     *
     * @access  public
     * @param   integer $customer_id The customer ID
     * @return  boolean
     */
    function hasIncidentsLeft($customer_id)
    {
    }


    /**
     * Checks whether the active contract associated with the given customer ID
     * is a per-incident contract or not.
     *
     * @access  public
     * @param   integer $customer_id The customer ID
     * @return  boolean
     */
    public function hasPerIncidentContract($customer_id)
    {
        return false;
    }


    /**
     * Returns the total number of allowed incidents for the given support
     * contract ID.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @param   integer $support_no The support contract ID
     * @return  integer The total number of incidents
     */
    function getTotalIncidents($support_no)
    {
    }


    /**
     * Returns the number of incidents remaining for the given support
     * contract ID.
     *
     * @access  public
     * @param   integer $support_no The support contract ID
     * @return  integer The number of incidents remaining.
     */
    function getIncidentsRemaining($support_no)
    {
    }


    /**
     * Method used to send a notice that the per-incident limit being reached.
     *
     * @access  public
     * @param   integer $contact_id The customer contact ID
     * @param   integer $customer_id The customer ID
     * @param   boolean $new_issue If the customer just tried to create a new issue.
     * @return  void
     */
    function sendIncidentLimitNotice($contact_id, $customer_id, $new_issue = false)
    {
    }


    /**
     * Returns a list of customers (companies) in the customer database.
     *
     * @access  public
     * @return  array An associated array of customers.
     */
    function getAssocList()
    {
    }


    /**
     * Method used to get the customer names for the given customer id.
     *
     * @access  public
     * @param   integer $customer_id The customer ID
     * @return  string The customer name
     */
    function getTitle($customer_id)
    {
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
    }


    /**
     * Method used to get the list of email addresses associated with the
     * contacts of a given customer.
     *
     * @access  public
     * @param   integer $customer_id The customer ID
     * @return  array The list of email addresses
     */
    function getContactEmailAssocList($customer_id)
    {
    }


    /**
     * Method used to get the customer and customer contact IDs associated
     * with a given list of email addresses.
     *
     * @access  public
     * @param   array $emails The list of email addresses
     * @return  array The customer and customer contact ID
     */
    function getCustomerIDByEmails($emails)
    {
    }


    /**
     * Method used to get the overall statistics of issues in the system for a
     * given customer.
     *
     * @access  public
     * @param   integer $customer_id The customer ID
     * @return  array The customer related issue statistics
     */
    function getOverallStats($customer_id)
    {
    }


    /**
     * Method used to build the overall customer profile from the information
     * stored in the customer database.
     *
     * @access  public
     * @param   integer $usr_id The Eventum user ID
     * @return  array The customer profile information
     */
    function getProfile($usr_id)
    {
    }


    /**
     * Method used to get the contract details for a given customer contact.
     *
     * @access  public
     * @param   integer $contact_id The customer contact ID
     * @return  array The customer contract details
     */
    function getContractDetails($contact_id, $restrict_expiration = TRUE)
    {
    }


    /**
     * Method used to get the details associated with a customer contact.
     *
     * @access  public
     * @param   integer $contact_id The customer contact ID
     * @return  array The contact details
     */
    function getContactDetails($contact_id)
    {
    }


    /**
     * Returns the list of customer IDs that are associated with the given
     * email value (wildcards welcome).
     *
     * @access  public
     * @param   string $email The email value
     * @return  array The list of customer IDs
     */
    function getCustomerIDsLikeEmail($email)
    {
    }


    /**
     * Method used to notify the customer contact that an existing issue
     * associated with him was just marked as closed.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @param   integer $contact_id The customer contact ID
     * @return  void
     */
    function notifyIssueClosed($issue_id, $contact_id)
    {
    }


    /**
     * Performs a customer lookup and returns the matches, if
     * appropriate.
     *
     * @access  public
     * @param   string $field The field that we are trying to search against
     * @param   string $value The value that we are searching for
     * @param   array  $options An array of options for search
     * @return  array The list of customers
     */
    function lookup($field, $value, $options)
    {
    }


    /**
     * Method used to notify the customer contact that a new issue was just
     * created and associated with his Eventum user.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @param   integer $contact_id The customer contact ID
     * @return  void
     */
    function notifyCustomerIssue($issue_id, $contact_id)
    {
    }


    /**
     * Method used to get the list of available support levels.
     *
     * @access  public
     * @return  array The list of available support levels
     */
    function getSupportLevelAssocList()
    {
    }


    /**
     * Returns the support level of the current support contract for a given
     * customer ID.
     *
     * @access  public
     * @param   integer $customer_id The customer ID
     * @param   integer $contract_id The contract ID
     * @return  string The support contract level
     */
    function getSupportLevelID($customer_id, $contract_id = false)
    {
    }


    /**
     * Returns the list of customer IDs for a given support contract level.
     *
     * @access  public
     * @param   integer $support_level_id The support level ID
     * @param   mixed $support_options An integer or array of integers indicating various options to get customers with.
     * @return  array The list of customer IDs
     */
    function getListBySupportLevel($support_level_id, $support_options = false)
    {
    }


    /**
     * Returns an array of support levels grouped together.
     *
     * @access  public
     * @return  array an array of support levels.
     */
    function getGroupedSupportLevels()
    {
    }


    /**
     * Method used to send an expiration notice.
     *
     * @access  public
     * @param   integer $contact_id The customer contact ID
     * @param   boolean $is_expired Whether this customer is expired or not
     * @return  void
     */
    function sendExpirationNotice($contact_id, $is_expired = FALSE)
    {
    }


    /**
     * Checks whether the given technical contact ID is allowed in the current
     * support contract or not.
     *
     * @access  public
     * @param   integer $customer_contact_id The customer technical contact ID
     * @return  boolean
     */
    function isAllowedSupportContact($customer_contact_id)
    {
    }


    /**
     * Method used to get the associated customer and customer contact from
     * a given set of support emails. This is especially useful to automatically
     * associate an issue to the appropriate customer contact that sent a
     * support email.
     *
     * @access  public
     * @param   array $sup_ids The list of support email IDs
     * @return  array The customer and customer contact ID
     */
    function getCustomerInfoFromEmails($sup_ids)
    {
    }


    /**
     * Method used to send an email notification to the sender of a
     * set of email messages that were manually converted into an
     * issue.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @param   array $sup_ids The email IDs
     * @param   integer $customer_id The customer ID
     * @return  array The list of recipient emails
     */
    function notifyEmailConvertedIntoIssue($issue_id, $sup_ids, $customer_id = FALSE)
    {
    }


    /**
     * Method used to send an email notification to the sender of an
     * email message that was automatically converted into an issue.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @param   string $sender The sender of the email message (and the recipient of this notification)
     * @param   string $date The arrival date of the email message
     * @param   string $subject The subject line of the email message
     * @return  void
     */
    function notifyAutoCreatedIssue($issue_id, $sender, $date, $subject)
    {
    }


    /**
     * Method used to get the customer login grace period (number of days).
     *
     * @access  public
     * @return  integer The customer login grace period
     */
    function getExpirationOffset()
    {
    }


    /**
     * Method used to get the details of the given customer contact.
     *
     * @access  public
     * @param   integer $contact_id The customer contact ID
     * @return  array The customer details
     */
    function getContactLoginDetails($contact_id)
    {
    }


    /**
     * Returns the end date of the current support contract for a given
     * customer ID.
     *
     * @access  public
     * @param   integer $customer_id The customer ID
     * @param   integer $contract_id The contract ID
     * @return  string The support contract end date
     */
    function getContractEndDate($customer_id, $contract_id = false)
    {
    }


    /**
     * Returns the name and email of the sales account manager of the given customer ID.
     *
     * @access  public
     * @param   integer $customer_id The customer ID
     * @return  array An array containing the name and email of the sales account manager
     */
    function getSalesAccountManager($customer_id)
    {
    }


    /**
     * Returns the start date of the current support contract for a given
     * customer ID.
     *
     * @access  public
     * @param   integer $customer_id The customer ID
     * @param   integer $contract_id The contract ID
     * @return  string The support contract start date
     */
    function getContractStartDate($customer_id, $contract_id = false)
    {
    }


    function getSupportLevelsByIssues(&$result)
    {
    }
}
