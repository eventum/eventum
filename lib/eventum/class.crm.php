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

use Eventum\Db\Adapter\AdapterInterface;
use Eventum\Db\DatabaseException;
use Eventum\Extension\ExtensionLoader;

define('CRM_EXCLUDE_EXPIRED', 'exclude_expired');

abstract class CRM
{
    /**
     * The connection to the database
     *
     * @var MDB2_Driver_Common
     */
    protected $connection;

    /**
     * Holds instances of the CRM backends
     *
     * @var     CRM[]
     */
    private static $instances = [];

    /**
     * The Project ID for this instance
     */
    protected $prj_id;

    /**
     * Holds an an array of support levels for this backend.
     *
     * @var array
     */
    protected $support_levels;

    /**
     * Setups a new instance for the specified project. If the instance already exists,
     * return the current instance.
     *
     * @param   int $prj_id The Project ID
     * @return  CRM An instance of a CRM class
     */
    public static function getInstance($prj_id)
    {
        if (!isset(self::$instances[$prj_id])) {
            self::$instances[$prj_id] = self::getBackendByProject($prj_id);
        }

        return self::$instances[$prj_id];
    }

    abstract public function authenticateCustomer();

    /**
     * Returns the customer object for the specified ID
     *
     * @param   string $customer_id A customer ID
     * @return  Customer A customer object
     */
    abstract public function getCustomer($customer_id);

    /**
     * Returns a contract object
     *
     * @param   int $contract_id A contract id
     * @return  Contract A contract object
     */
    abstract public function getContract($contract_id);

    /**
     * Returns a contact object for the specified contact ID
     *
     * @param   int $email
     * @return  Contact A contact object
     */
    abstract public function getContactByEmail($email);

    /**
     * Returns a contact object for the specified email address
     *
     * @param   int $contact_id
     * @return  Contact A contact object
     */
    abstract public function getContact($contact_id);

    /**
     * Returns the name of the backend.
     *
     * @return  string
     */
    abstract public function getName();

    /**
     * Performs a customer lookup and returns the matches, if
     * appropriate.
     *
     * @param   string $field The field that we are trying to search against
     * @param   string $value The value that we are searching for
     * @param   $options
     * @return  array The list of customers
     */
    abstract public function lookup($field, $value, $options);

    /**
     * Setups the backend for use. Generally will be used to establish a connection to a database
     * or preload data.
     *
     * @param   int $prj_id
     */
    abstract protected function setup($prj_id);

    /**
     * Returns an array of incident types supported.
     *
     * @return  array An array of per incident types
     */
    abstract public function getIncidentTypes();

    /**
     * Returns an associative array of support level IDs => names
     *
     * @return array
     */
    abstract public function getSupportLevelAssocList();

    /**
     * Returns information on the specified support level
     *
     * @param   string $level_id the level to return info for
     * @throws  SupportLevelNotFoundException
     * @return  Support_Level
     */
    abstract public function getSupportLevel($level_id);

    /**
     * Returns support levels grouped together
     *
     * @return array
     */
    abstract public function getGroupedSupportLevels();

    /**
     * Retrieves the customer titles and support levels associated with the given list of issues. Should set
     * the following keys for each row, 'customer_title', 'support_level'
     *
     * @param   array $result The list of issues
     * @see     Search::getListing()
     */
    abstract public function processListIssuesResult(&$result);

    /**
     * Retrieves the customer titles for the specified IDS
     *
     * @param   array $ids The list of customer IDs
     */
    abstract public function getCustomerTitles($ids);

    /**
     * Retrieves the response countdown deadline associated with the
     * given list of issues.
     *
     * @param   array $result The list of issues
     * @see     Search::getListing()
     */
    abstract public function getResponseCountdownByIssues(&$result);

    /**
     * Method used to get an associative array of all companies
     * available, in a format of customer ID => company name.
     *
     * @param   string|bool  $search_string A string to search for
     * @param   bool $include_expired If expired customers should be included
     * @param   int|bool $limit The maximum number of records to return
     * @param   int|bool $customer_ids The ids to limit the results too
     * @return  array The associative array of companies
     */
    abstract public function getCustomerAssocList($search_string = false, $include_expired = false, $limit = false,
                                                    $customer_ids = false);

    /**
     * Method used to get an associative array of all contracts
     * available, in a format of contract ID => contract details.
     *
     * @param   string|bool  $search_string A string to search for
     * @return  array The associative array of contracts
     */
    abstract public function getContractAssocList($search_string = false);

    /**
     * Method used to get an associative array of all contacts
     * available, in a format of contact ID => contact details.
     *
     * @param   string|bool  $search_string A string to search for
     * @return  array The associative array of contacts
     */
    abstract public function getContactAssocList($search_string = false);

    /**
     * Returns the list of customer IDs that are associated with the given
     * keyword value (wildcards welcome). This can search name, emails, etc
     *
     * FIXME: Search::buildWhereClause uses "true" for $options
     *
     * @param   string $keyword The string to search by value
     * @param array $options
     * @return  array The list of customer IDs
     */
    abstract public function getCustomerIDsByString($keyword, $options = []);

    /**
     * Method used to get the associated customer and customer contact from
     * a given set of support emails. This is especially useful to automatically
     * associate an issue to the appropriate customer contact that sent a
     * support email.
     *
     * @param   array $sup_ids The list of support email IDs
     * @return  array The customer and customer contact ID
     */
    abstract public function getCustomerInfoFromEmails($sup_ids);

    /**
     * Method used to send an email notification to the sender of an
     * email message that was automatically converted into an issue.
     *
     * @param   int $issue_id The issue ID
     * @param   string $sender The sender of the email message (and the recipient of this notification)
     * @param   string $date The arrival date of the email message
     * @param   string $subject The subject line of the email message
     */
    abstract public function notifyAutoCreatedIssue($issue_id, $sender, $date, $subject);

    /**
     * Method used to send an email notification to the sender of a
     * set of email messages that were manually converted into an
     * issue.
     *
     * @param   int $issue_id The issue ID
     * @param   array $sup_ids The email IDs
     * @param   int|bool $contract_id The contract ID
     * @return  array The list of recipient emails
     */
    abstract public function notifyEmailConvertedIntoIssue($issue_id, $sup_ids, $contract_id = false);

    /**
     * Returns a list of customer IDS belonging to the specified support level
     *
     * FIXME: Reminder::buildWhereClauses uses string[] for $support_options but that is not documented
     *
     * @param   string|array $levels The support Level ID or an array of support level ids
     * @param   int|int[] $support_options an integer or array of integers indicating various options to get customers with
     * @return  array
     */
    abstract public function getCustomerIDsBySupportLevel($levels, $support_options = false);

    /**
     * Returns an array of all active contacts for the specified customer ids
     *
     * @param   array $customer_ids
     * @return  array
     */
    abstract public function getContactIDsByCustomer($customer_ids);

    /**
     * Returns the list of contract IDs for a given support contract level.
     *
     * @param   int $level_id The support level ID
     * @param   mixed $support_options an integer or array of integers indicating various options to get customers with
     * @return  array The list of contract IDs
     */
    abstract public function getContractIDsBySupportLevel($level_id, $support_options = false);

    /**
     * Checks whether the given project ID is setup to use customer integration
     * or not.
     *
     * @param   int $prj_id The project ID
     * @return  bool
     */
    public static function hasCustomerIntegration($prj_id)
    {
        $backend = self::getBackendNameByProject($prj_id);

        return !empty($backend);
    }

    /**
     * Returns the customer backend class file associated with the given
     * project ID.
     *
     * @param   int $prj_id The project ID
     * @return  string The customer backend class filename
     */
    public static function getBackendNameByProject($prj_id)
    {
        static $backends;

        if (isset($backends[$prj_id])) {
            return $backends[$prj_id];
        }

        $stmt = 'SELECT
                    prj_customer_backend
                 FROM
                    `project`
                 WHERE prj_id=?';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, [$prj_id]);
        } catch (DatabaseException $e) {
            $res = false;
        }

        // $res can be empty string if not configured
        // or null if no row was fetched (impossible?)
        // or false if there was db error

        return $backends[$prj_id] = $res;
    }

    /**
     * Includes the appropriate customer backend class associated with the
     * given project ID, instantiates it and returns the class.
     *
     * @param   int $prj_id The project ID
     * @return bool|CRM
     */
    private static function getBackendByProject($prj_id)
    {
        $backend_class = self::getBackendNameByProject($prj_id);
        if (!$backend_class) {
            return false;
        }

        /** @var CRM $backend */
        $backend = static::getExtensionLoader()->createInstance($backend_class);
        $backend->setup($prj_id);
        $backend->prj_id = $prj_id;

        return $backend;
    }

    /**
     * Method used to get the list of technical account managers
     * currently available in the system.
     *
     * @return  array The list of account managers
     */
    public static function getAccountManagerList()
    {
        $stmt = 'SELECT
                    cam_id,
                    cam_prj_id,
                    cam_customer_id,
                    cam_type,
                    usr_full_name
                 FROM
                    `customer_account_manager`,
                    `user`
                 WHERE
                    cam_usr_id=usr_id';
        try {
            $res = DB_Helper::getInstance()->getAll($stmt);
        } catch (DatabaseException $e) {
            return '';
        }

        foreach ($res as &$row) {
            $crm = self::getInstance($row['cam_prj_id']);
            try {
                $customer = $crm->getCustomer($row['cam_customer_id']);
                $row['customer_title'] = $customer->getName();
            } catch (CRMException $e) {
            }
        }

        return $res;
    }

    /**
     * Method used to add a new association of Eventum user =>
     * customer ID. This association will provide the basis for a
     * new role of technical account manager in Eventum.
     *
     * @return  int 1 if the insert worked properly, any other value otherwise
     */
    public static function insertAccountManager()
    {
        $stmt = 'INSERT INTO
                    `customer_account_manager`
                 (
                    cam_prj_id,
                    cam_customer_id,
                    cam_usr_id,
                    cam_type
                 ) VALUES (
                    ?, ?, ?, ?
                 )';
        try {
            DB_Helper::getInstance()->query(
                $stmt, [$_POST['project'], $_POST['customer'], $_POST['manager'], $_POST['type']]
            );
        } catch (DatabaseException $e) {
            return -1;
        }

        return 1;
    }

    /**
     * Method used to get the details of a given account manager.
     *
     * @param   int $cam_id The account manager ID
     * @return  array The account manager details
     */
    public static function getAccountManagerDetails($cam_id)
    {
        $stmt = 'SELECT
                    *
                 FROM
                    `customer_account_manager`
                 WHERE
                    cam_id=?';
        try {
            $res = DB_Helper::getInstance()->getRow($stmt, [$cam_id]);
        } catch (DatabaseException $e) {
            return [];
        }

        return $res;
    }

    /**
     * Method used to update the details of an account manager.
     *
     * @return  int 1 if the update worked properly, any other value otherwise
     */
    public static function updateAccountManager()
    {
        $stmt = 'UPDATE
                    `customer_account_manager`
                 SET
                    cam_prj_id=?,
                    cam_customer_id=?,
                    cam_usr_id=?,
                    cam_type=?
                 WHERE
                    cam_id=?';
        try {
            DB_Helper::getInstance()->query(
                $stmt, [$_POST['project'], $_POST['customer'], $_POST['manager'], $_POST['type'], $_POST['id']]
            );
        } catch (DatabaseException $e) {
            return -1;
        }

        return 1;
    }

    /**
     * Method used to remove a technical account manager from the
     * system.
     *
     * @return  bool
     */
    public static function removeAccountManager()
    {
        $items = $_POST['items'];
        $stmt = 'DELETE FROM
                    `customer_account_manager`
                 WHERE
                    cam_id IN (' . DB_Helper::buildList($items) . ')';
        try {
            DB_Helper::getInstance()->query($stmt, $items);
        } catch (DatabaseException $e) {
            return false;
        }

        return true;
    }

    /**
     * Method used to get the list of technical account managers for
     * a given customer ID.
     *
     * @param   int $prj_id The project ID
     * @param   int $customer_id The customer ID
     * @return  array The list of account managers
     */
    public static function getAccountManagers($prj_id, $customer_id)
    {
        $stmt = 'SELECT
                    cam_usr_id,
                    usr_email,
                    cam_type
                 FROM
                    `customer_account_manager`,
                    `user`
                 WHERE
                    cam_usr_id=usr_id AND
                    cam_prj_id=? AND
                    cam_customer_id=?';
        try {
            $res = DB_Helper::getInstance()->fetchAssoc($stmt, [$prj_id, $customer_id], AdapterInterface::DB_FETCHMODE_ASSOC);
        } catch (DatabaseException $e) {
            return [];
        }

        if (empty($res)) {
            return [];
        }

        return $res;
    }

    /**
     * Returns any notes for for the specified customer.
     *
     * @param   int $customer_id The customer ID
     * @return  array an array containg the note details
     */
    public static function getNoteDetailsByCustomer($customer_id)
    {
        $stmt = 'SELECT
                    cno_id,
                    cno_prj_id,
                    cno_customer_id,
                    cno_note
                FROM
                    `customer_note`
                WHERE
                    cno_customer_id = ?';
        try {
            $res = DB_Helper::getInstance()->getRow($stmt, [$customer_id]);
        } catch (DatabaseException $e) {
            return [];
        }

        return $res;
    }

    /**
     * Returns any note details for for the specified id.
     *
     * @param $cno_id
     * @return  array an array containg the note details
     */
    public static function getNoteDetailsByID($cno_id)
    {
        $stmt = 'SELECT
                    cno_prj_id,
                    cno_customer_id,
                    cno_note
                FROM
                    `customer_note`
                WHERE
                    cno_id = ?';
        try {
            $res = DB_Helper::getInstance()->getRow($stmt, [$cno_id]);
        } catch (DatabaseException $e) {
            return [];
        }

        return $res;
    }

    /**
     * Returns an array of notes for all customers.
     *
     * @return  array an array of notes
     */
    public static function getNoteList()
    {
        $stmt = 'SELECT
                    cno_id,
                    cno_prj_id,
                    cno_customer_id,
                    cno_note
                FROM
                    `customer_note`
                ORDER BY
                    cno_customer_id ASC';
        try {
            $res = DB_Helper::getInstance()->getAll($stmt);
        } catch (DatabaseException $e) {
            return [];
        }

        foreach ($res as &$row) {
            try {
                $crm = self::getInstance($row['cno_prj_id']);
                $row['customer_title'] = $crm->getCustomer($row['cno_customer_id'])->getName();
            } catch (Exception $e) {
            }
        }

        return $res;
    }

    /**
     * Updates a note.
     *
     * @param   int $cno_id the id of this note
     * @param   int $prj_id The project ID
     * @param   int $customer_id the id of the customer
     * @param   string $note the text of this note
     * @return int
     */
    public static function updateNote($cno_id, $prj_id, $customer_id, $note)
    {
        $stmt = 'UPDATE
                    `customer_note`
                 SET
                    cno_note=?,
                    cno_prj_id=?,
                    cno_customer_id=?,
                    cno_updated_date=?
                 WHERE
                    cno_id=?';
        try {
            DB_Helper::getInstance()->query(
                $stmt, [$note, $prj_id, $customer_id, Date_Helper::getCurrentDateGMT(), $cno_id]
            );
        } catch (DatabaseException $e) {
            return -1;
        }

        return 1;
    }

    /**
     * Adds a quick note for the specified customer.
     *
     * @param   int $prj_id The project ID
     * @param   int $customer_id the id of the customer
     * @param   string  $note the note to add
     * @return int
     */
    public static function insertNote($prj_id, $customer_id, $note)
    {
        $stmt = 'INSERT INTO
                    `customer_note`
                 (
                    cno_prj_id,
                    cno_customer_id,
                    cno_created_date,
                    cno_updated_date,
                    cno_note
                 ) VALUES (
                    ?, ?, ?, ?, ?
                 )';
        try {
            DB_Helper::getInstance()->query(
                $stmt,
                [$prj_id, $customer_id, Date_Helper::getCurrentDateGMT(), Date_Helper::getCurrentDateGMT(), $note]
            );
        } catch (DatabaseException $e) {
            return -1;
        }

        return 1;
    }

    /**
     * Removes the selected notes from the database.
     *
     * @param   array $ids an array of cno_id's to be deleted
     * @return int
     */
    public static function removeNotes($ids)
    {
        $stmt = 'DELETE FROM
                    `customer_note`
                 WHERE
                    cno_id IN (' . DB_Helper::buildList($ids) . ')';
        try {
            DB_Helper::getInstance()->query($stmt, $ids);
        } catch (DatabaseException $e) {
            return -1;
        }

        return 1;
    }

    public function getConnection()
    {
        return $this->connection;
    }

    public function getProjectID()
    {
        return $this->prj_id;
    }

    /**
     * Returns the number of days expired contracts are allowed to login.
     *
     * @return  int the number of days
     */
    abstract public function getExpirationOffset();

    abstract public function getTemplatePath();

    abstract public function getHtdocsPath();

    public function __toString()
    {
        return "CRM Instance\nProject ID: " . $this->prj_id . "\nClass Name: " . get_class($this);
    }

    /**
     * Helper function to return customer name.
     * @param int $prj_id
     * @param string $customer_id
     * @return string
     */
    public static function getCustomerName($prj_id, $customer_id)
    {
        try {
            $crm = self::getInstance($prj_id);
            $customer = $crm->getCustomer($customer_id);

            return $customer->getName();
        } catch (CRMException $e) {
            return null;
        }
    }

    /**
     * @return ExtensionLoader
     * @internal
     */
    public static function getExtensionLoader()
    {
        $dirs = [
            APP_INC_PATH . '/crm',
            APP_LOCAL_PATH . '/crm',
        ];

        return new ExtensionLoader($dirs, '%s', 'CRM');
    }
}
