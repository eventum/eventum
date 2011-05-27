<?php

abstract class CRM
{
    /**
     * The connection to the database
     *
     * @var resource
     */
    protected $connection;

    /**
     * Holds instances of the CRM backends
     *
     * @var     CRM[]
     */
    private static $instances = array();

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
     * An array containing information about options.
     * $option_id = array(
     *          'name'  =>  $option_name,
     *          'per_incident'  =   1 or 0
     * )
     *
     * @var array
     */
    protected $options_info = array();

    /**
     * Setups a new instance for the specified project. If the instance already exists,
     * return the current instance.
     *
     * @param   integer $prj_id The Project ID
     * @return  CRM An instance of a CRM class
     */
    public static function getInstance($prj_id)
    {
        if (!isset(self::$instances[$prj_id])) {
            self::$instances[$prj_id] = self::getBackendByProject($prj_id);
        }
        return self::$instances[$prj_id];
    }


    public static function destroyInstances()
    {
        foreach (self::$instances as $prj_id => $instance) {
            $instance->destroy($prj_id);
        }
        self::$instances = array();
    }


    public static function authenticateCustomer($prj_id = false)
    {
        // Create later
    }


    /**
     * If a single customer ID is passed in a single Customer object is returned. If an array
     * is passed in an array of customer objects are returned.
     *
     * @param   integer $customer_id A customer id or array of ids
     * @return  CRM_Customer A customer object or an array of customer objects
     */
    abstract public function &getCustomer($customer_id);


    /**
     * Returns a contract object
     *
     * @param   integer $contract_id A contract id
     * @return  Contract A contract object
     */
    abstract public function &getContract($contract_id);
    

    /**
     * Returns a contact object for the specified contact ID
     *
     * @param   integer $email
     * @return  Contact A contact object
     */
    abstract public function getContactByEmail($email);


    /**
     * Returns a contact object for the specified email address
     *
     * @param   integer $contact_id
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
     * @param   boolean $include_expired Whether to include expired/cancelled customers or not (optional)
     * @param   boolean $include_future Whether to include expired/cancelled customers or not (optional)
     * @return  array The list of customers
     */
    abstract public function lookup($field, $value, $include_expired = FALSE, $include_future = false);


    /**
     * Method used to notify the customer contact that a new issue was just
     * created and associated with his Eventum user.
     *
     * @param   integer $issue_id The issue ID
     * @param   integer $contact_id The customer contact ID
     * @return  void
     */
    abstract public function notifyCustomerNewIssue($issue_id, $contact_id);


    /**
     * Setups the backend for use. Generally will be used to establish a connection to a database
     * or preload data.
     *
     * @param   integer $prj_id
     */
    abstract protected function setup($prj_id);


    /**
     * destroys the backend
     *
     * @param   integer $prj_id
     */
    abstract protected function destroy($prj_id);


    /**
     * Re-initializes the object. This is useful for long running processes where the connection may time out
     *
     */
    abstract public function reinitialize();


    /**
     * Returns an array of incident types supported.
     *
     * @return  array An array of per incident types
     */
    abstract public function getIncidentTypes();


    /**
     * Checks whether the given issue ID was marked as a redeemed incident or
     * not.
     *
     * @param   integer $issue_id The issue ID
     * @param   integer $incident_type The type of incident
     * @return  boolean
     */
    abstract public function isRedeemedIncident($issue_id, $incident_type);


    /**
     * Returns an array of the curently redeemed incident types for the issue.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @return  array An array containing the redeemed incident types
     */
    abstract public function getRedeemedIncidentDetails($issue_id);


    /**
     * Returns an array of support levels for a specific type
     *
     * @param   string $type The type of level we want to return
     * @return  array()
     */
    abstract public function getLevelsByType($type);


    /**
     * Returns an associative array of support level IDs => names
     *
     * @param   mixed   $type The type of levels to return (optional)
     * @return array
     */
    abstract public function getSupportLevelAssocList($type = false);


    /**
     * Returns information on the specified support level
     *
     * @param   integer $level_id The level to return info for.
     * @return  array An array of information about the level
     */
    abstract public function getSupportLevelDetails($level_id);


    /**
     * Returns an array of support levels grouped together.
     *
     * @return array
     */
    abstract public function getGroupedSupportLevels();

    /**
     * Retrieves the customer titles associated with the given list of issues.
     *
     * @param   array $result The list of issues
     * @see     Search::getListing()
     */
    abstract public function getCustomerTitlesByIssues(&$result);


    /**
     * Retrieves the customer titles for the specified IDS
     *
     * @param   array $ids The list of customer IDs
     */
    abstract public function getCustomerTitles($ids);


    /**
     * Retrieves the customer support levels associated with the
     * given list of issues.
     *
     * @param   array $result The list of issues
     * @see     Search::getListing()
     */
    abstract public function getSupportLevelsByIssues(&$result);


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
     * @param   string|boolean  $search_string A string to search for
     * @param   boolean $include_expired If expired customers should be included
     * @param   integer|boolean $limit The maximum number of records to return
     * @param   integer|boolean $customer_ids The ids to limit the results too
     * @return  array The associative array of companies
     */
    abstract public function getCustomerAssocList($search_string = false, $include_expired = false, $limit = false,
                                                    $customer_ids = false);


    /**
     * Method used to get an associative array of all contracts
     * available, in a format of contract ID => contract details.
     *
     * @param   string|boolean  $search_string A string to search for
     * @return  array The associative array of contracts
     */
    abstract public function getContractAssocList($search_string = false);


    /**
     * Method used to get an associative array of all contacts
     * available, in a format of contact ID => contact details.
     *
     * @param   string|boolean  $search_string A string to search for
     * @return  array The associative array of contacts
     */
    abstract public function getContactAssocList($search_string = false);


    /**
     * Returns the list of customer IDs that are associated with the given
     * email value (wildcards welcome).
     *
     * @param   string $email The email value
     * @param   boolean $include_expired If expired contracts should be included
     * @return  array The list of customer IDs
     */
    abstract public function getCustomerIDsLikeEmail($email, $include_expired = false);


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
     * Method used to get the customer and customer contact IDs associated
     * with a given list of email addresses.
     *
     * @param   array $emails The list of email addresses
     * @param   boolean $include_expired If expired contacts should be excluded
     * @return  array The customer and customer contact ID
     */
    abstract public function getCustomerAndContactIDByEmails($emails, $include_expired = false);


    /**
     * Method used to send an email notification to the sender of an
     * email message that was automatically converted into an issue.
     *
     * @param   integer $issue_id The issue ID
     * @param   string $sender The sender of the email message (and the recipient of this notification)
     * @param   string $date The arrival date of the email message
     * @param   string $subject The subject line of the email message
     * @return  void
     */
    abstract public function notifyAutoCreatedIssue($issue_id, $sender, $date, $subject);


    /**
     * Method used to send an email notification to the sender of a
     * set of email messages that were manually converted into an
     * issue.
     *
     * @param   integer $issue_id The issue ID
     * @param   array $sup_ids The email IDs
     * @param   integer|boolean $contract_id The contract ID
     * @return  array The list of recipient emails
     */
    abstract public function notifyEmailConvertedIntoIssue($issue_id, $sup_ids, $contract_id = FALSE);


    /**
     * Returns a list of customer IDS belonging to the specified support level
     *
     * @param   integer $support_level_id The support Level ID
     * @param   mixed $support_options An integer or array of integers indicating various options to get customers with.
     * @return  array
     */
    abstract public function getCustomerIDsBySupportLevel($support_level_id, $support_options = false);


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
     * @param   integer $support_level_id The support level ID
     * @param   mixed $support_options An integer or array of integers indicating various options to get customers with.
     * @return  array The list of contract IDs
     */
    abstract public function getContractIDsBySupportLevel($support_level_id, $support_options = FALSE);

    /**
     * Checks whether the given project ID is setup to use customer integration
     * or not.
     *
     * @param   integer $prj_id The project ID
     * @return  boolean
     */
    public static function hasCustomerIntegration($prj_id)
    {
        $backend = CRM::getBackendNameByProject($prj_id);
        if (empty($backend)) {
            return false;
        } else {
            return true;
        }
    }


    /**
     * Returns the list of available customer backends by listing the class
     * files in the backend directory.
     *
     * @return  array Associative array of filename => name
     */
    static function getBackendList()
    {
        $files = Misc::getFileList(APP_INC_PATH . "crm/backends");
        $files = array_merge($files, Misc::getFileList(APP_LOCAL_PATH. '/crm'));
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
    static function getBackendNameByProject($prj_id)
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
     * @access  private
     * @param   integer $prj_id The project ID
     * @return  boolean
     */
    private static function getBackendByProject($prj_id)
    {
        $backend_class = CRM::getBackendNameByProject($prj_id);
        if (empty($backend_class)) {
            return false;
        }
        return self::getBackend($backend_class, $prj_id);
    }


    /**
     * Returns the backend for the specified class name
     *
     * @param   string $class_name The name of the class.
     * @return  Customer
     */
    private static function getBackend($backend_class, $prj_id = 0)
    {
        $file_name_chunks = explode(".", $backend_class);
        $class_name = $file_name_chunks[1];


        if (file_exists(APP_LOCAL_PATH . "/crm/$class_name/$backend_class")) {
            require_once(APP_LOCAL_PATH . "/crm/" . $class_name . "/$backend_class");
        } else {
            require_once APP_INC_PATH . "/crm/backends/" . $class_name . "/$backend_class";
        }

        $backend = new $class_name;
        $backend->setup($prj_id);
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
        $stmt = "SELECT
                    cam_id,
                    cam_prj_id,
                    cam_customer_contract_id,
                    cam_type,
                    usr_full_name
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "customer_account_manager,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
                 WHERE
                    cam_usr_id=usr_id";
        $res = DB_Helper::getInstance()->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            for ($i = 0; $i < count($res); $i++) {
                $crm = CRM::getInstance($res[$i]['cam_prj_id']);
                $contract = $crm->getContract($res[$i]['cam_customer_contract_id']);
                $res[$i]['contract_title'] = $contract->getTitle();
            }
            return $res;
        }
    }


    /**
     * Method used to add a new association of Eventum user =>
     * customer ID. This association will provide the basis for a
     * new role of technical account manager in Eventum.
     *
     * @return  integer 1 if the insert worked properly, any other value otherwise
     */
    public static function insertAccountManager()
    {
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "customer_account_manager
                 (
                    cam_prj_id,
                    cam_customer_contract_id,
                    cam_usr_id,
                    cam_type
                 ) VALUES (
                    " . $_POST['project'] . ",
                    " . $_POST['contract'] . ",
                    " . $_POST['manager'] . ",
                    '" . $_POST['type'] . "'
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
     * @param   integer $cam_id The account manager ID
     * @return  array The account manager details
     */
    public static function getAccountManagerDetails($cam_id)
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
     * @return  integer 1 if the update worked properly, any other value otherwise
     */
    public static function updateAccountManager()
    {
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "customer_account_manager
                 SET
                    cam_prj_id=" . Misc::escapeInteger($_POST['project']) . ",
                    cam_customer_contract_id=" . Misc::escapeInteger($_POST['contract']) . ",
                    cam_usr_id=" . Misc::escapeInteger($_POST['manager']) . ",
                    cam_type='" . Misc::escapeString($_POST['type']) . "'
                 WHERE
                    cam_id=" . Misc::escapeInteger($_POST['id']);
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
     * @return  boolean
     */
    public static function removeAccountManager()
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
     * a given contract ID.
     *
     * @param   integer $prj_id The project ID
     * @param   integer $contract_id The contract ID
     * @return  array The list of account managers
     */
    public function getAccountManagers($prj_id, $contract_id)
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
                    cam_customer_contract_id=" . Misc::escapeInteger($contract_id);
        $res = DB_Helper::getInstance()->getAssoc($stmt, false, array(), DB_FETCHMODE_ASSOC);
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
    

    public function getConnection()
    {
        return $this->connection;
    }

    public function getProjectID()
    {
        return $this->prj_id;
    }

    public function getOptionsInfo($opt_id)
    {
        return $this->options_info[$opt_id];
    }


    /**
     * Returns the number of days expired contracts are allowed to login.
     *
     * @return  integer The number of days.
     */
    abstract public function getExpirationOffset();


    public function __toString()
    {
        return "CRM Instance\nProject ID: " . $this->prj_id . "\nClass Name: " . get_class($this);
    }
}