<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004 MySQL AB                                    |
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
//
// @(#) $Id: s.class.status.php 1.5 04/01/09 05:04:10-00:00 jpradomaia $
//

$customer_db = false;

class Customer_Backend
{
    function connect()
    {
        $dsn = array(
            'phptype'  => "mysql",
            'hostspec' => "localhost",
            'database' => "spot",
            'username' => "root",
            'password' => ""
        );
        $GLOBALS['customer_db'] = DB::connect($dsn);
    }


    /**
     * Method used to get the support contract status for a given customer.
     *
     * @access  public
     * @param   integer $customer_id The customer ID
     * @return  string The support contract status
     */
    function getContractStatus($customer_id)
    {
        static $returns;

        // poor man's caching system...
        if (!empty($returns[$customer_id])) {
            return $returns[$customer_id];
        }

        $stmt = "SELECT
                    UNIX_TIMESTAMP(enddate)
                 FROM
                    support
                 WHERE
                    cust_no=$customer_id
                 ORDER BY
                    support_no DESC
                 LIMIT
                    0, 1";
        $res = $GLOBALS["customer_db"]->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            $status = 'expired';
        } else {
            // if we cannot find a support entry for this customer, he is 'expired'
            if (empty($res)) {
                $status = 'expired';
            } else {
                $current_gmt_ts = Date_API::getCurrentUnixTimestampGMT();
                $grace_period_offset = Customer::getExpirationOffset() * DAY;
                $cutoff_ts = $res + $grace_period_offset;
                if ($current_gmt_ts < $res) {
                    $status = 'active';
                } else {
                    if (($current_gmt_ts >= $res) && ($current_gmt_ts <= $cutoff_ts)) {
                        $status = 'in_grace_period';
                    } else {
                        $status = 'expired';
                    }
                }
            }
        }

        $returns[$customer_id] = $status;
        return $status;
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
        $ids = array();
        for ($i = 0; $i < count($result); $i++) {
            if (!empty($result[$i]["iss_customer_id"])) {
                $ids[] = $result[$i]["iss_customer_id"];
            }
        }
        $ids = array_unique(array_values($ids));
        if (count($ids) == 0) {
            return false;
        }
        $ids = implode(", ", $ids);
        $stmt = "SELECT
                    cust_no,
                    name
                 FROM
                    cust_entity
                 WHERE
                    cust_no IN ($ids)";
        $titles = $GLOBALS["customer_db"]->getAssoc($stmt);
        if (PEAR::isError($titles)) {
            Error_Handler::logError(array($titles->getMessage(), $titles->getDebugInfo()), __FILE__, __LINE__);
        } else {
            // now populate the $result variable again
            for ($i = 0; $i < count($result); $i++) {
                @$result[$i]['customer_title'] = $titles[$result[$i]['iss_customer_id']];
            }
        }
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
        static $returns;

        // poor man's caching system...
        if (!empty($returns[$customer_id])) {
            return $returns[$customer_id];
        }

        $stmt = "SELECT
                    A.name,
                    B.support_no,
                    B.order_row_no,
                    CONCAT(C.customer_type, ' ', C.level, ' Support') AS support_level,
                    B.enddate AS expiration_date,
                    CONCAT(YEAR(B.startdate), '-', B.support_id) AS contract_id
                 FROM
                    cust_entity A,
                    support B,
                    support_type C
                 WHERE
                    A.cust_no=$customer_id AND
                    B.cust_no=A.cust_no AND
                    B.support_type_no=C.support_type_no AND
                    B.startdate <= NOW()
                 ORDER BY
                    B.enddate DESC
                 LIMIT
                    0, 1";
        $res = $GLOBALS["customer_db"]->getRow($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        } else {
            if (empty($res)) {
                return array();
            } else {
                list($is_per_incident, $options) = Customer_Backend::getSupportOptions($res['support_no']);
                list($account_manager, ) = Customer_Backend::getSalesAccountManager($customer_id);
                $returns[$customer_id] = array(
                    'support_no'        => $res['support_no'],
                    'sales_person'      => Customer_Backend::getSalesPerson($customer_id, $res['order_row_no']),
                    'account_manager'   => $account_manager,
                    'customer_name'     => $res['name'],
                    'contract_id'       => $res['contract_id'],
                    'support_level'     => $res['support_level'],
                    'support_options'   => @implode(", ", $options),
                    'support_exp_date'  => $res['expiration_date'],
                    'note'              => Customer::getNoteDetailsByCustomer($customer_id),
                    'is_per_incident'   => $is_per_incident
                );
                return $returns[$customer_id];
            }
        }
    }


    /**
     * Returns the list of support options associated with a given support
     * contract ID.
     *
     * @access  public
     * @param   integer $support_no The support contract ID
     * @return  array The list of support options
     */
    function getSupportOptions($support_no)
    {
        // get the extra options for this support contract
        $stmt = "SELECT
                    B.descript,
                    A.contract_type,
                    A.parameter
                 FROM
                    support_extra A,
                    pl_extra B
                 WHERE
                    A.support_no=$support_no AND
                    A.pl_extra_no=B.pl_extra_no
                 ORDER BY
                    B.descript ASC";
        $extra = $GLOBALS["customer_db"]->getAll($stmt, DB_FETCHMODE_ASSOC);
        $extra_options = array();
        $is_per_incident = false;
        for ($i = 0; $i < count($extra); $i++) {
            if ($extra[$i]['contract_type'] == 'perIncident') {
                // get the current usage and the limit
                $incidents_left = ((integer) $extra[$i]['parameter']) - Customer_Backend::getIncidentUsage($support_no);
                $extra_options[] = $extra[$i]['descript'] . ' (Total: ' . $extra[$i]['parameter'] . '; Left: ' . $incidents_left . ')';
                $is_per_incident = true;
            } else {
                $extra_options[] = $extra[$i]['descript'];
            }
        }
        return array(
            $is_per_incident,
            $extra_options
        );
    }


    /**
     * Returns the total of incidents already redeemed in the given support
     * contract ID.
     *
     * @access  public
     * @param   integer $support_no The support contract ID
     * @return  integer The total of incidents already redeemed
     */
    function getIncidentUsage($support_no)
    {
        $stmt = "SELECT
                    COUNT(*)
                 FROM
                    support_issue
                 WHERE
                    support_no=$support_no";
        return $GLOBALS["customer_db"]->getOne($stmt);
    }


    /**
     * Returns the name of the sales account manager of the given customer ID.
     *
     * @access  public
     * @param   integer $customer_id The customer ID
     * @return  string The name of the sales account manager
     */
    function getSalesAccountManager($customer_id)
    {
        $stmt = "SELECT
                     A.name,
                     A.email
                 FROM
                     user A,
                     cust_sper B
                 WHERE
                     A.user_no=B.user_no AND
                     B.cust_no=$customer_id AND
                     B.cs_role_no=1";
        return $GLOBALS["customer_db"]->getRow($stmt);
    }


    /**
     * Returns the name of the sales person who sold the given order to the 
     * customer ID.
     *
     * @access  public
     * @param   integer $customer_id The customer ID
     * @param   integer $order_id The order ID
     * @return  string The name of the sales person
     */
    function getSalesPerson($customer_id, $order_id)
    {
        $stmt = "SELECT
                     A.name
                 FROM
                     user A,
                     support B,
                     order_row C,
                     order_head D
                 WHERE
                     A.user_no=D.our_ref AND
                     B.order_row_no=C.order_row_no AND
                     C.order_no=D.order_no AND
                     B.order_row_no=$order_id AND
                     B.cust_no=$customer_id";
        return $GLOBALS["customer_db"]->getOne($stmt);
    }


    /**
     * Checks whether the given issue ID was marked as a redeemed incident or
     * not.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @return  boolean
     */
    function isRedeemedIncident($issue_id)
    {
        $details = Customer_Backend::getDetails(Issue::getCustomerID($issue_id));
        $stmt = "SELECT
                    COUNT(*)
                 FROM
                    support_issue
                 WHERE
                    support_no=" . $details['support_no'] . " AND
                    iss_id=$issue_id";
        $res = $GLOBALS["customer_db"]->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            if ($res > 0) {
                return true;
            } else {
                return false;
            }
        }
    }


    /**
     * Method used to get an associative array of all companies
     * available in Spot, in a format of customer ID => company name.
     *
     * @access  public
     * @return  array The associative array of companies
     */
    function getAssocList()
    {
        $stmt = "SELECT
                    A.cust_no,
                    A.name
                 FROM
                    cust_entity A,
                    support B
                 WHERE
                    A.cust_type='C' AND
                    A.cust_no=B.cust_no AND
                    B.status <> 'Cancelled' AND
                    NOW() <= (B.enddate + INTERVAL " . Customer_Backend::_getExpirationOffset() . " DAY)
                 ORDER BY
                    A.name ASC";
        $res = $GLOBALS["customer_db"]->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to get the customer login grace period (number of days).
     *
     * @access  public
     * @return  integer The customer login grace period
     */
    function _getExpirationOffset()
    {
        $setup = Setup::load();
        if (empty($setup['customer_grace_period'])) {
            $setup['customer_grace_period'] = 14; // XXX: need to create a config constant for this eventually
        }
        return $setup['customer_grace_period'];
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
        $stmt = "SELECT
                    A.name
                 FROM
                    cust_entity A,
                    support B
                 WHERE
                    A.cust_type='C' AND
                    A.cust_no=$customer_id AND
                    A.cust_no=B.cust_no AND
                    B.status <> 'Cancelled' AND
                    NOW() <= (B.enddate + INTERVAL " . Customer_Backend::_getExpirationOffset() . " DAY)";
        $res = $GLOBALS["customer_db"]->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }
}
?>