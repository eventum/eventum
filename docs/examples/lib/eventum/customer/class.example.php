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
// | Authors: Bryan Alsdorf <bryan@mysql.com>                             |
// +----------------------------------------------------------------------+
//


/**
 * Example customer backend. This does not cover all functionality, but should provide an idea
 * on how to implement a backend.
 *
 * @author Bryan Alsdorf <bryan@mysql.com>
 */
class Example_Customer_Backend extends Abstract_Customer_Backend
{
    // array of customer data used for this example
    var $data;

    /**
     * Overide the connect method to populate a variable instead of connecting to a database
     */
    function connect()
    {
        $this->data = array(
            1 => array(
                "customer_id"     => 1,
                "customer_name"   => "Bryan's widget factory",
                "start_date"      => '2004-03-10',
                "expiration_date" => '2010-03-10',
                "contacts" => array(
                    array(
                        'contact_id' => 87,
                        'first_name' => 'Bryan',
                        'last_name'  => 'Alsdorf',
                        'email'      => 'bryan@example.com',
                        'phone'      => '+1 (123) 456-7890'
                    ),
                    array(
                        'contact_id' => 93,
                        'first_name' => 'Bob',
                        'last_name'  => 'Smith',
                        'email'      => 'bob@example.com',
                        'phone'      => '+1 (123) 456-7890'
                    )
                ),
                "address"          => "1234 Blah Street,\nHouston, TX 12345",
                "support_level_id" => 1,
                "account_manager"  => array("Sales guy", "Salesguy@example.com")
            ),
            2 => array(
                "customer_id"      => 2,
                "customer_name"    => "Joao, Inc.",
                "start_date"       => '2004-08-01',
                "expiration_date"  => '2005-08-01',
                "contacts"         => array(
                    array(
                        'contact_id' => 67,
                        'first_name' => 'Joao',
                        'last_name'  => 'Prado Maia',
                        'email'      => 'jpm@example.com',
                        'phone'      => '+1 (123) 456-7890'
                    )
                ),
                "address"          => "123 Fake Street,\nSpringfield, USA",
                "support_level_id" => 3,
                "account_manager"  => array("Sales guy", "Salesguy@example.com")
            ),
            3 => array(
                "customer_id"     => 3,
                "customer_name"   => "Example Corp.",
                "start_date"      => '2002-01-01',
                "expiration_date" => '2006-01-01',
                "contacts"        => array(
                    array(
                        'contact_id' => 21,
                        'first_name' => 'J',
                        'last_name'  => 'Man',
                        'email'      => 'j-man@example.com',
                        'phone'      => '+1 (123) 456-7890'
                    ),
                    array(
                        'contact_id' => 22,
                        'first_name' => 'John',
                        'last_name'  => 'Doe',
                        'email'      => 'John.Doe@example.com',
                        'phone'      => '+1 (123) 456-7890'
                    )
                ),
                "address"            => "56789 Some drive,\nFooo, Foo 12345",
                "support_level_id"   => 4,
                "account_manager"    => array("Sales guy", "Salesguy@example.com")
            )
        );
    }


    /**
     * Returns the name of the backend
     *
     * @return  string The name of the backend
     */
    function getName()
    {
        return "example";
    }


    /**
     * Returns true if the backend uses support levels, false otherwise
     *
     * @access  public
     * @return  boolean True if the project uses support levels.
     */
    function usesSupportLevels()
    {
        // this example will use support levels so override parent method
        return true;
    }


    /**
     * Returns the contract status associated with the given customer ID.
     * Possible return values are 'active', 'in_grace_period' and 'expired'.
     *
     * @access  public
     * @param   integer $customer_id The customer ID
     * @return  string The contract status
     */
    function getContractStatus($customer_id)
    {
        // active contracts have an expiration date in the future
        $expiration = strtotime($this->data[$customer_id]['expiration_date']);
        $now = Date_Helper::getCurrentUnixTimestampGMT();
        if ($expiration > $now) {
            return 'active';
        } elseif ($expiration > ($now + (Date_Helper::DAY * $this->getExpirationOffset()))) {
            return 'in_grace_period';
        } else {
            return 'expired';
        }
    }


    /**
     * Retrieves the customer titles associated with the given list of issues.
     *
     * @access  public
     * @param   array $result The list of issues
     * @see     Search::getListing()
     */
    function getCustomerTitlesByIssues(&$result)
    {
        if (count($result) > 0) {
            for ($i = 0; $i < count($result); $i++) {
                if (!empty($result[$i]["iss_customer_id"])) {
                    $result[$i]["customer_title"] = $this->getTitle($result[$i]["iss_customer_id"]);
                }
            }
        }
    }


    /**
     * Retrieves the support levels associated with the given list of issues.
     *
     * @access  public
     * @param   array $result The list of issues
     * @see     Search::getListing()
     */
    function getSupportLevelsByIssues(&$result)
    {
        if (count($result) > 0) {
            $support_levels = $this->getSupportLevelAssocList();
            for ($i = 0; $i < count($result); $i++) {
                if (!empty($result[$i]["iss_customer_id"])) {
                    $result[$i]["support_level"] = @$support_levels[$this->getSupportLevelID($result[$i]['iss_customer_id'])];
                }
            }
        }
    }


    /**
     * Method used to get the details of the given customer.
     *
     * @access  public
     * @param   integer $customer_id The customer ID
     * @param   boolean $force_refresh If the cache should not be used.
     * @param   integer $contract_id The contract ID
     * @return  array The customer details
     */
    function getDetails($customer_id, $force_refresh = false, $contract_id = false)
    {
        $support_levels = $this->getSupportLevelAssocList();
        $details = $this->data[$customer_id];
        $details["support_level"] = $support_levels[$details["support_level_id"]];
        $details["contract_status"] = $this->getContractStatus($customer_id);
        $details["note"] = Customer::getNoteDetailsByCustomer($customer_id);
        return $details;
    }


    // PLEASE NOTE:
    // This example does not implement per-incident
    // support so those methods will not be included here


    /**
     * Returns a list of customers (companies) in the customer database.
     *
     * @access  public
     * @return  array An associated array of customers.
     */
    function getAssocList()
    {
        $assoc = array();
        foreach ($this->data as $id => $details) {
            $assoc[$id] = $details["customer_name"];
        }
        return $assoc;
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
        return $this->data[$customer_id]["customer_name"];
    }


    /**
     * Method used to get an associative array of the customer names
     * for the given list of customer ids.
     *
     * @access  public
     * @param   array $customer_ids The list of customers
     * @return  array The associative array of customer id => customer name
     */
    function getTitles($customer_ids)
    {
        $assoc = array();
        foreach ($this->data as $id => $details) {
            if (in_array($id, $customer_ids)) {
                $assoc[$id] = $details["customer_name"];
            }
        }
        return $assoc;
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
        $assoc = array();
        foreach ($this->data[$customer_id]['contacts'] as $key => $contact) {
            $assoc[] = $contact["email"];
        }
        return $assoc;
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
        $assoc = array();
        foreach ($this->data as $company_id => $details) {
            foreach ($details['contacts'] as $contact) {
                // in a perfect world you would want to do partial searches
                // here, but as an example in_array() will do
                if (in_array($contact["email"], $emails)) {
                    return array($company_id, $contact['contact_id']);
                }
            }
        }
        return $assoc;
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
        // don't count customer notes, emails, phone calls
        $stmt = "SELECT
                    iss_id,
                    sta_is_closed
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                 LEFT JOIN
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
                 ON
                    iss_sta_id=sta_id
                 WHERE
                    iss_customer_id=$customer_id";
        $res = DB_Helper::getInstance()->getAssoc($stmt);
        if ((PEAR::isError($res)) || (empty($res)) || (count($res) == 0)) {
            return array(
                'total_issues'           => 0,
                'total_open'             => 0,
                'total_closed'           => 0,
                'total_persons'          => 0,
                'total_emails'           => 0,
                'total_calls'            => 0,
                'average_first_response' => 0,
                'average_close'          => 0
            );
        } else {
            $issues = array();
            $open = 0;
            $closed = 0;
            foreach ($res as $issue_id => $status) {
                $issues[] = $issue_id;
                if (empty($status)) {
                    $open++;
                } else {
                    $closed++;
                }
            }
        }

        // get the list of distinct persons from the notification
        // list, phone support and notes tables
        $stmt = "SELECT
                    iss_id,
                    sub_usr_id,
                    not_usr_id,
                    phs_usr_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                 LEFT JOIN
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "note
                 ON
                    not_iss_id=iss_id
                 LEFT JOIN
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "phone_support
                 ON
                    phs_iss_id=iss_id
                 LEFT JOIN
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "subscription
                 ON
                    sub_iss_id=iss_id AND
                    sub_usr_id <> 0 AND
                    sub_usr_id IS NOT NULL
                 LEFT JOIN
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "subscription_type
                 ON
                    sbt_sub_id=sub_id AND
                    sbt_type='emails'
                 WHERE
                    iss_customer_id=$customer_id";
        $res = DB_Helper::getInstance()->getAll($stmt, DB_FETCHMODE_ASSOC);
        $persons = array();
        for ($i = 0; $i < count($res); $i++) {
            if ((!empty($res[$i]['sub_usr_id'])) && (!in_array($res[$i]['sub_usr_id'], $persons))) {
                $persons[] = $res[$i]['sub_usr_id'];
            }
            if ((!empty($res[$i]['not_usr_id'])) && (!in_array($res[$i]['not_usr_id'], $persons))) {
                $persons[] = $res[$i]['not_usr_id'];
            }
            if ((!empty($res[$i]['phs_usr_id'])) && (!in_array($res[$i]['phs_usr_id'], $persons))) {
                $persons[] = $res[$i]['phs_usr_id'];
            }
        }

        // get list of staff emails
        $stmt = "SELECT
                    usr_email
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_user,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                 WHERE
                    pru_usr_id=usr_id AND
                    pru_prj_id=iss_prj_id AND
                    iss_id=$issue_id AND
                    pru_role <> " . User::getRoleID('Customer');
        $staff_emails = DB_Helper::getInstance()->getCol($stmt);

        $stmt = "SELECT
                    sup_from
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email
                 WHERE
                    sup_iss_id IN (" . implode(", ", $issues) . ")";
        $emails = DB_Helper::getInstance()->getCol($stmt);
        $total_emails = 0;
        foreach ($emails as $address) {
            $email = strtolower(Mail_Helper::getEmailAddress($address));
            $staff_emails = array_map('strtolower', $staff_emails);
            if (@in_array($email, $staff_emails)) {
                $total_emails++;
            }
        }

        $stmt = "SELECT
                    COUNT(*)
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "phone_support
                 WHERE
                    phs_iss_id IN (" . implode(", ", $issues) . ")";
        $calls = DB_Helper::getInstance()->getOne($stmt);

        $stmt = "SELECT
                    AVG(UNIX_TIMESTAMP(iss_first_response_date) - UNIX_TIMESTAMP(iss_created_date)) AS first_response_time
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                 WHERE
                    iss_id IN (" . implode(", ", $issues) . ")";
        $avg_first_response = DB_Helper::getInstance()->getOne($stmt);
        if (!empty($avg_first_response)) {
            // format the average into a number of minutes
            $avg_first_response = $avg_first_response / 60;
        }

        $stmt = "SELECT
                    AVG(UNIX_TIMESTAMP(iss_closed_date) - UNIX_TIMESTAMP(iss_created_date)) AS closed_time
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                 WHERE
                    iss_id IN (" . implode(", ", $issues) . ")";
        $avg_close = DB_Helper::getInstance()->getOne($stmt);
        if (!empty($avg_close)) {
            // format the average into a number of minutes
            $avg_close = $avg_close / 60;
        }

        return array(
            'total_issues'           => count($issues),
            'total_open'             => $open,
            'total_closed'           => $closed,
            'total_persons'          => count($persons),
            'total_emails'           => $total_emails,
            'total_calls'            => (integer) $calls,
            'average_first_response' => Misc::getFormattedTime($avg_first_response),
            'average_close'          => Misc::getFormattedTime($avg_close)
        );
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
        // this is used to return all details about the customer/contact in one fell swoop.
        // for this example it will just return the details
        return $this->getDetails(User::getCustomerID($usr_id));
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
        $assoc = array();
        foreach ($this->data as $company_id => $details) {
            foreach ($details['contacts'] as $contact) {
                if ($contact['contact_id'] == $contact_id) {
                    $contact['customer_id'] = $company_id;
                    return $contact;
                }
            }
        }
        return $assoc;
    }


    /**
     * Returns the list of customer IDs that are associated with the given
     * email value (wildcards welcome). Contrary to the name of the method, this
     * also works with customer names
     *
     * @access  public
     * @param   string $email The email value
     * @return  array The list of customer IDs
     */
    function getCustomerIDsLikeEmail($email)
    {
        $ids = array();
        foreach ($this->data as $customer) {
            if (stristr($customer['customer_name'], $email) !== false) {
                $ids[] = $customer['customer_id'];
                continue;
            }

            foreach ($customer['contacts'] as $contact) {
                if (stristr($contact['email'], $email) !== false) {
                    $ids[] = $customer['customer_id'];
                }
            }
        }
        return $ids;
    }


    /**
     * Performs a customer lookup and returns the matches, if
     * appropriate.
     *
     * @access  public
     * @param   string $field The field that we are trying to search against
     * @param   string $value The value that we are searching for
     * @return  array The list of customers
     */
    function lookup($field, $value)
    {
        if ($field == "email") {
            $details = $this->getCustomerIDsLikeEmail($value);
            if (count($details) > 0) {
                list($id, $contact_id) = $details;
            } else {
                $id = 0;
            }
        } elseif ($field == "customer_id") {
            if (empty($this->data[$value])) {
                $id = 0;
            } else {
                $id = $value;
            }
        }
        if ($id > 0) {
            return array($this->getDetails($id));
        }
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
        // send a notification email to your customer here
    }


    /**
     * Method used to get the list of available support levels.
     *
     * @access  public
     * @return  array The list of available support levels
     */
    function getSupportLevelAssocList()
    {
        return array(
            1 => "Normal 1",
            2 => "Normal 2",
            3 => "Enhanced",
            4 => "Ultra-Special"
        );
    }


    /**
     * Returns the support level of the current support contract for a given
     * customer ID.
     *
     * @access  public
     * @param   integer $customer_id The customer ID
     * @return  string The support contract level
     */
    function getSupportLevelID($customer_id)
    {
        return $this->data[$customer_id]["support_level_id"];
    }


    /**
     * Returns the list of customer IDs for a given support contract level.
     *
     * @access  public
     * @param   integer/array $support_level_id The support level ID or an array of support level IDs
     * @param   mixed $support_options An integer or array of integers indicating various options to get customers with.
     * @return  array The list of customer IDs
     */
    function getListBySupportLevel($support_level_id, $support_options = false)
    {
        if (!is_array($support_level_id)) {
            $support_level_id = array($support_level_id);
        }
        $assoc = array();
        foreach ($this->data as $company_id => $details) {
            if (in_array($details["support_level_id"], $support_level_id)) {
                $assoc[] = $company_id;
            }
        }
        return $assoc;
    }


    /**
     * Returns an array of support levels grouped together.
     *
     * @access  public
     * @return  array an array of support levels.
     */
    function getGroupedSupportLevels()
    {
        return array(
            "Normal"        => array(1,2),
            "Enhanced"      => array(3),
            "Ultra-Special" => array(4)
        );
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
        foreach ($this->data as $id => $details) {
            foreach ($details['contacts'] as $contact) {
                if ($contact['contact_id'] == $customer_contact_id) {
                    return true;
                }
            }
        }
        return false;
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
        $senders = Support::getSender($sup_ids);
        if (count($senders) > 0) {
            $emails = array();
            for ($i = 0; $i < count($senders); $i++) {
                $emails[] = Mail_Helper::getEmailAddress($senders[$i]);
            }
            list($customer_id, $contact_id) = $this->getCustomerIDByEmails($emails);
            $company = $this->getDetails($customer_id);
            $contact = $this->getContactDetails($contact_id);
            return array(
                'customer_id'   => $customer_id,
                'customer_name' => $company['customer_name'],
                'contact_id'    => $contact_id,
                'contact_name'  => $contact['first_name'] . " " . $contact['last_name'],
                'contacts'      => $this->getContactEmailAssocList($customer_id)
            );
        } else {
            return array(
                'customer_id'   => '',
                'customer_name' => '',
                'contact_id'    => '',
                'contact_name'  => '',
                'contacts'      => ''
            );
        }
    }


    /**
     * Method used to get the customer login grace period (number of days).
     *
     * @access  public
     * @return  integer The customer login grace period
     */
    function getExpirationOffset()
    {
        // customers can log in up to 30 days after the contract expires.
        return 30;
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
        $stmt = "SELECT
                    usr_email,
                    usr_password,
                    usr_full_name
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
                 WHERE
                    usr_customer_contact_id = $contact_id";
        $res = DB_Helper::getInstance()->getRow($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            if (empty($res)) {
                return -2;
            } else {
                return $res;
            }
        }
    }


    /**
     * Returns the end date of the current support contract for a given
     * customer ID.
     *
     * @access  public
     * @param   integer $customer_id The customer ID
     * @return  string The support contract end date
     */
    function getContractEndDate($customer_id)
    {
        return $this->data[$customer_id]['expiration_date'];
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
        return $this->data[$customer_id]['account_manager'];
    }


    /**
     * Returns the start date of the current support contract for a given
     * customer ID.
     *
     * @access  public
     * @param   integer $customer_id The customer ID
     * @return  string The support contract start date
     */
    function getContractStartDate($customer_id)
    {
        return $this->data[$customer_id]['start_date'];
    }


    /**
     * Returns a message to be displayed to a customer on the top of the issue creation page.
     *
     * @param   array $customer_id Customer ID.
     */
    function getNewIssueMessage($customer_id)
    {
        // we could do anything we wanted in here, but we will just say "hi"
        return "Hi! Please create a new issue";
    }


    /**
     * Checks whether the given customer has a support contract that
     * enforces limits for the minimum first response time or not.
     *
     * @access  public
     * @param   integer $customer_id The customer ID
     * @return  boolean
     */
    function hasMinimumResponseTime($customer_id)
    {
        $support_level = $this->getSupportLevelID($customer_id);
        if ($support_level == 1 || $support_level == 2) {
            return true;
        }
    }


    /**
     * Returns the minimum first response time in seconds for the
     * support level associated with the given customer.
     *
     * @access  public
     * @param   integer $customer_id The customer ID
     * @return  integer The minimum first response time
     */
    function getMinimumResponseTime($customer_id)
    {
        // normal level customers will not recieve a response for atleast a day
        $support_level = $this->getSupportLevelID($customer_id);
        if ($support_level == 1 || $support_level == 2) {
            return (60 * 60 * 24);
        }
    }


    /**
     * Returns the maximum first response time associated with the
     * support contract of the given customer.
     *
     * @access  public
     * @param   integer $customer_id The customer ID
     * @return  integer The maximum first response time, in seconds
     */
    function getMaximumFirstResponseTime($customer_id)
    {
        $support_level = $this->getSupportLevelID($customer_id);
        if ($support_level == 1 || $support_level == 2) {
            // 2 days for normal
            return (60 * 60 * 24 * 2);
        } elseif ($support_level == 3) {
            // 1 day for special
            return (60 * 60 * 24);
        } elseif ($support_level == 4) {
            // 30 minutes for special
            return (60 *30);
        }
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
        // send a support expiration notice email to your customer here
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
        // send a notification email to your customer here
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
        // send a notification email to your customer here
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
        // send a notification email to your customer here
    }


    /**
     * Method used to get the contract details for a given customer contact.
     *
     * @access  public
     * @param   integer $contact_id The customer contact ID
     * @return  array The customer contract details
     */
    function getContractDetails($contact_id, $restrict_expiration)
    {
        $contact = $this->getContactDetails($contact_id);
        $customer = $this->getDetails($contact['customer_id']);
        $support_levels = $this->getSupportLevelAssocList();

        return array(
            'contact_name'    => $contact['first_name'] . ' ' . $contact['last_name'],
            'company_name'    => $customer['customer_name'],
            'contract_id'     => $customer['customer_id'],
            'support_level'   => $support_levels[$customer['support_level_id']],
            'expiration_date' => $customer['expiration_date']
        );
    }

}
