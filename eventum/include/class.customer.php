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

include_once(APP_INC_PATH . 'class.misc.php');

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
        $files = Misc::getFileList(APP_INC_PATH . "customer");
        $list = array();
        for ($i = 0; $i < count($files); $i++) {
            // make sure we only list the customer backends
            if (preg_match('/^class\./', $files[$i])) {
                // display a prettyfied backend name in the admin section
                preg_match('/class\.(.*)\.php/', $files[$i], $matches);
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
     * @access  public
     * @param   integer $prj_id The project ID
     * @return  string The customer backend class filename
     */
    function _getBackendNameByProject($prj_id)
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
        $res = $GLOBALS["db_api"]->dbh->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return '';
        } else {
            $backends = $res;
            return $backends[$prj_id];
        }
    }


    /**
     * Includes the appropriate customer backend class associated with the
     * given project ID, instantiates it and returns the class.
     *
     * @access  private
     * @param   integer $prj_id The project ID
     * @return  boolean
     */
    function &_getBackend($prj_id)
    {
        static $setup_backends;

        if (empty($setup_backends[$prj_id])) {
            $backend_class = Customer::_getBackendNameByProject($prj_id);
            if (empty($backend_class)) {
                return false;
            }
            $file_name_chunks = explode(".", $backend_class);
            $class_name = $file_name_chunks[1] . "_Customer_Backend";
            
            include_once(APP_INC_PATH . "customer/$backend_class");
            
            $setup_backends[$prj_id] = new $class_name;
            $setup_backends[$prj_id]->connect();
        }
        return $setup_backends[$prj_id];
    }


    /**
     * Checks whether the given project ID is setup to use customer integration
     * or not.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @return  boolean
     */
    function hasCustomerIntegration($prj_id)
    {
        $backend = Customer::_getBackendNameByProject($prj_id);
        if (empty($backend)) {
            return false;
        } else {
            return true;
        }
    }


    function getBackendImplementationName($prj_id)
    {
        $backend_class = Customer::_getBackendNameByProject($prj_id);
        if (empty($backend_class)) {
            return '';
        }
        $file_name_chunks = explode(".", $backend_class);
        return $file_name_chunks[1];
    }


    function doesBackendUseSupportLevels($prj_id)
    {
        $backend =& Customer::_getBackend($prj_id);
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
     * @access  public
     * @param   integer $prj_id The project ID
     * @param   integer $customer_id The customer ID
     * @return  string The contract status
     */
    function getContractStatus($prj_id, $customer_id)
    {
        $backend =& Customer::_getBackend($prj_id);
        return $backend->getContractStatus($customer_id);
    }



    /**
     * Retrieves the customer titles associated with the given list of issues.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @param   array $result The list of issues
     * @see     Issue::getListing()
     */
    function getCustomerTitlesByIssues($prj_id, &$result)
    {
        $backend =& Customer::_getBackend($prj_id);
        $backend->getCustomerTitlesByIssues($result);
    }


    /**
     * Method used to get the details of the given customer.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @param   integer $customer_id The customer ID
     * @return  array The customer details
     */
    function getDetails($prj_id, $customer_id)
    {
        $backend =& Customer::_getBackend($prj_id);
        return $backend->getDetails($customer_id);
    }


    function isRedeemedIncident($prj_id, $issue_id)
    {
        $backend =& Customer::_getBackend($prj_id);
        return $backend->isRedeemedIncident($issue_id);
    }


    function getAssocList($prj_id)
    {
        $backend =& Customer::_getBackend($prj_id);
        return $backend->getAssocList();
    }


    function getTitle($prj_id, $customer_id)
    {
        $backend =& Customer::_getBackend($prj_id);
        return $backend->getTitle($customer_id);
    }


    function getTitles($prj_id, $customer_ids)
    {
        $backend =& Customer::_getBackend($prj_id);
        return $backend->getTitles($customer_ids);
    }


    function getContactEmailAssocList($prj_id, $customer_id)
    {
        $backend =& Customer::_getBackend($prj_id);
        return $backend->getContactEmailAssocList($customer_id);
    }


    function getCustomerIDByEmails($prj_id, $emails)
    {
        $backend =& Customer::_getBackend($prj_id);
        return $backend->getCustomerIDByEmails($emails);
    }


    function getOverallStats($prj_id, $customer_id)
    {
        $backend =& Customer::_getBackend($prj_id);
        return $backend->getOverallStats($customer_id);
    }


    function getProfile($prj_id, $usr_id)
    {
        $backend =& Customer::_getBackend($prj_id);
        return $backend->getProfile($usr_id);
    }


    function getContractDetails($prj_id, $contact_id, $restrict_expiration = TRUE)
    {
        $backend =& Customer::_getBackend($prj_id);
        return $backend->getContractDetails($contact_id, $restrict_expiration);
    }


    function getContactDetails($prj_id, $contact_id)
    {
        $backend =& Customer::_getBackend($prj_id);
        return $backend->getContactDetails($contact_id);
    }


    function getCustomerIDsLikeEmail($prj_id, $email)
    {
        $backend =& Customer::_getBackend($prj_id);
        return $backend->getCustomerIDsLikeEmail($email);
    }


    function flagIncident($prj_id, $issue_id)
    {
        $backend =& Customer::_getBackend($prj_id);
        return $backend->flagIncident($issue_id);
    }


    function unflagIncident($prj_id, $issue_id)
    {
        $backend =& Customer::_getBackend($prj_id);
        return $backend->unflagIncident($issue_id);
    }


    /**
     * Method used to notify the customer contact that an existing issue
     * associated with him was just marked as closed.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @param   integer $issue_id The issue ID
     * @param   integer $contact_id The customer contact ID
     * @return  void
     */
    function notifyIssueClosed($prj_id, $issue_id, $contact_id)
    {
        $backend =& Customer::_getBackend($prj_id);
        return $backend->notifyIssueClosed($issue_id, $contact_id);
    }


    function lookup($prj_id, $field, $value)
    {
        $backend =& Customer::_getBackend($prj_id);
        return $backend->lookup($field, $value);
    }


    function notifyCustomerIssue($prj_id, $issue_id, $contact_id)
    {
        $backend =& Customer::_getBackend($prj_id);
        return $backend->notifyCustomerIssue($issue_id, $contact_id);
    }



    function getSupportLevelAssocList($prj_id)
    {
        $backend =& Customer::_getBackend($prj_id);
        return $backend->getSupportLevelAssocList();
    }


    function getSupportLevelID($prj_id, $customer_id)
    {
        $backend =& Customer::_getBackend($prj_id);
        return $backend->getSupportLevelID($customer_id);
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
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            for ($i = 0; $i < count($res); $i++) {
                $res[$i]['customer_title'] = Customer::getTitle($res[$i]['cam_prj_id'], $res[$i]['cam_customer_id']);
            }
            return $res;
        }
    }


    /**
     * Method used to add a new association of Eventum user => Spot
     * customer ID. This association will provide the basis for a
     * new role of technical account manager in Eventum.
     *
     * @access  public
     * @return  integer 1 if the insert worked properly, any other value otherwise
     */
    function insertAccountManager()
    {
        global $HTTP_POST_VARS;

        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "customer_account_manager
                 (
                    cam_prj_id,
                    cam_customer_id,
                    cam_usr_id,
                    cam_type
                 ) VALUES (
                    " . $HTTP_POST_VARS['project'] . ",
                    " . $HTTP_POST_VARS['customer'] . ",
                    " . $HTTP_POST_VARS['manager'] . ",
                    '" . $HTTP_POST_VARS['type'] . "'
                 )";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
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
                    cam_id=$cam_id";
        $res = $GLOBALS["db_api"]->dbh->getRow($stmt, DB_FETCHMODE_ASSOC);
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
        global $HTTP_POST_VARS;

        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "customer_account_manager
                 SET
                    cam_prj_id=" . $HTTP_POST_VARS['project'] . ",
                    cam_customer_id=" . $HTTP_POST_VARS['customer'] . ",
                    cam_usr_id=" . $HTTP_POST_VARS['manager'] . ",
                    cam_type='" . $HTTP_POST_VARS['type'] . "'
                 WHERE
                    cam_id=" . $HTTP_POST_VARS['id'];
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
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
        global $HTTP_POST_VARS;

        $items = @implode(", ", $HTTP_POST_VARS["items"]);
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "customer_account_manager
                 WHERE
                    cam_id IN ($items)";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            return true;
        }
    }


    /**
     * Method used to get the list of technical account managers for
     * a given Spot customer ID.
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
                    usr_email
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "customer_account_manager,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
                 WHERE
                    cam_usr_id=usr_id AND
                    cam_prj_id=$prj_id AND
                    cam_customer_id=$customer_id";
        $res = $GLOBALS["db_api"]->dbh->getAssoc($stmt);
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
                    cno_customer_id = $customer_id";
        $res = $GLOBALS['db_api']->dbh->getRow($stmt, DB_FETCHMODE_ASSOC);
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
                    cno_id = $cno_id";
        $res = $GLOBALS['db_api']->dbh->getRow($stmt, DB_FETCHMODE_ASSOC);
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
        $res = $GLOBALS['db_api']->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        } else {
            for ($i = 0; $i < count($res); $i++) {
                $res[$i]['customer_title'] = Customer::getTitle($res[$i]['cno_prj_id'], $res[$i]['cno_customer_id']);
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
                    cno_prj_id=$prj_id,
                    cno_customer_id=$customer_id,
                    cno_updated_date='" . Date_API::getCurrentDateGMT() . "'
                 WHERE
                    cno_id=$cno_id";
        $res = $GLOBALS['db_api']->dbh->query($stmt);
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
                    $prj_id,
                    $customer_id,
                    '" . Date_API::getCurrentDateGMT() . "',
                    '" . Date_API::getCurrentDateGMT() . "',
                    '" . Misc::escapeString($note) . "'
                 )";
        $res = $GLOBALS['db_api']->dbh->query($stmt);
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
                    cno_id IN (" . join(", ", $ids) . ")";
        $res = $GLOBALS['db_api']->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            return 1;
        }
    }
}
?>