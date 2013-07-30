<?php

/**
 * Abstract class representing a contract
 *
 * @author Bryan Alsdorf <balsdorf@gmail.com>
 */
abstract class Contract
{
    /**
     * Holds an instance of the customer object this contract belongs too.
     *
     * @var Customer
     */
    protected $customer;

    /**
     * Holds the database connection this object should use.
     *
     * @var resource
     */
    protected $connection;

    /**
     * If this contract exists
     *
     * @var boolean
     */
    protected $exists = false;

    /**
     * The ID of the contract this object represents
     *
     * @var integer
     */
    protected $contract_id;

    /**
     * The start date of this contract
     *
     * @var string
     */
    protected $start_date;

    /**
     * The end date of this contract
     *
     * @var string
     */
    protected $end_date;

    /**
     * The Support Level ID of this contract
     *
     * @var integer
     */
    protected $support_level_id;

    /**
     * The Support Level of this contract
     *
     * @var integer
     */
    protected $support_level;

    /**
     * The Status of this contract. Should be one of the following values:
     * 'active', 'expired', 'cancelled'.
     *
     * @var string
     */
    protected $status;

    /**
     * A public comment associated with this contract.
     *
     * @var string
     */
    protected $public_comment;

    /**
     * The number of servers for this contract.
     *
     * @var string
     */
    protected $server_count;

    /**
     * The options for this contract
     *
     * @var array
     */
    protected $options = array();

    /**
     * The partner ID for this contract.
     *
     * @var integer
     */
    protected $partner_id;

    /**
     * The amount of this contract (opportunity)
     *
     * @var double
     */
    protected $amount;

    /**
     * The currency the above amount is in
     *
     * @var string
     */
    protected $amount_currency;

    /**
     * Constructs the contract object and loads contract data.
     *
     * @param Customer $customer The parent customer object
     * @param integer $contract_id
     * @see Contract::load()
     */
    function __construct(CRM_Customer &$customer, $contract_id)
    {
        $this->customer = &$customer;
        $this->connection = &$customer->getConnection();
        $this->contract_id = $contract_id;

        // attempt to load the data
        $this->load();
    }


    /**
     * Convenience method for setting all contract data at once. This probably is only used by batch
     * scripts setting up customers.
     *
     * @param   string $start_date
     * @param   string $end_date
     * @param   integer $support_level_id
     * @param   string $status
     * @param   string $public_comment
     * @param   string $partner_id
     * @param   integer $server_count
     * @param   double $amount
     * @param   string $amount_currency
     */
    public function setData($start_date, $end_date, $support_level_id, $status, $public_comment, $partner_id,
                        $server_count, $amount, $amount_currency)
    {
        $this->start_date = $start_date;
        $this->end_date = $end_date;
        $this->support_level_id = $support_level_id;
        $this->status = $status;
        $this->public_comment = $public_comment;
        $this->partner_id = $partner_id;
        $this->server_count = $server_count;
        $this->amount = $amount;
        $this->amount_currency = $amount_currency;
    }


    /**
     * Sets the end date for this contract
     *
     * @param   string $end_date
     */
    public function setEndDate($end_date)
    {
        $this->end_date = $end_date;
    }


    /**
     * Saves the object to the database. Returns true on success or PEAR_Error otherwise
     *
     * @return  boolean Returns true on success or PEAR_Error otherwise
     */
    abstract public function save();


    /**
     * Loads contract information into the object. This method must set
     * this->exists to true if the contract exists and set it to false if
     * it doesn't exist.
     *
     * @see Contract::exists
     */
    abstract protected function load();


    /**
     * Returns a contact object for the specified contact ID. This should ONLY return
     * the contact if it is associated with this contract.
     *
     * @param   integer $contact_id
     * @return  Contact A contact object
     */
    abstract public function getContact($contact_id);


    /**
     * Returns an array contact objects for this contract
     *
     * @param   mixed $options An array of options that affect which contacts are returned.
     * @return  Contact
     */
    abstract public function getContacts($options = false);


    /**
     * Links the specified contact to this contract.
     *
     * @param   integer $contact_id
     */
    abstract public function linkContact($contact_id);


    /**
     * Unlinks the specified contact from this contract
     *
     * @param  integer $contact_id
     */
    abstract public function unLinkContact($contact_id);


    /**
     * Returns the options associated with this contact. If $include_extra_info is false returns
     * an associative array of $option_id => $option_value. If $include_extra_info is true then
     * an associative array of $option_id => array('name' => $name, 'value' => $value).
     *
     * @param   boolean $include_extra_info If extra info like option title should be included.
     * @return  array
     */
    abstract public function getOptions($include_extra_info = false);


    /**
     * Returns the value of the specified option, or false if the option is not set
     *
     * @param   integer $option_id The ID of the option
     * @return  mixed The value of the option or false.
     */
    abstract public function getOption($option_id);


    /**
     * Sets the support options in the contract. If the options need to be validated this method
     * should be overwritten.
     *
     * @param   array $options An associative array ($option_id => $value)
     */
    public function setOptions($options)
    {
        $this->options = $options;
    }


    /**
     * Returns true if this contract is expired, false otherwise.
     *
     * @return  Boolean
     */
    abstract public function isExpired();


    /**
     * Returns true if this contract is active, false otherwise.
     *
     * @return  Boolean
     */
    abstract public function isActive();


    /**
     * Returns the maximum first response time for this contract in minutes. If
     * the issue ID is provided the issue will be examined for options which might
     * affect the response time such as Severity.
     *
     * @param   integer $issue An array of issue details used to provide specified response time for (optional)
     * @return  integer The response time in seconds
     */
    abstract public function getMaximumFirstResponseTime($issue = false);


    /**
     * Returns an array of details about this contract
     *
     * @param   mixed $return_options An array of options that controls what data is returned.
     * @return  array
     */
    abstract public function getDetails($return_options = false);


    /**
     * Returns the total of incidents already redeemed in the given
     * contract ID.
     *
     * @param   integer $incident_type The type of incident
     * @return  integer The total of incidents already redeemed
     */
    abstract public function getIncidentUsage($incident_type);


    /**
     * Returns the total number of allowed incidents for the given support
     * contract ID.
     *
     * @param   integer $incident_type The type of incident
     * @return  integer The total number of incidents
     */
    abstract public function getTotalIncidents($incident_type);


    /**
     * Returns the number of incidents remaining for the given support
     * contract ID.
     *
     * @param   integer $contract_id The contract ID
     * @param   integer $incident_type The type of incident
     * @return  integer The number of incidents remaining.
     */
    abstract public function getIncidentsRemaining($incident_type);


    /**
     * Returns true if the contract is of $type
     *
     * @param   mixed $type The type or array of types to look for
     * @return  boolean
     */
    abstract public function isOfType($type);


    /**
     * Returns the minimum response time for a contract in seconds.
     *
     * @return  mixed The minimum response time or false.
     */
    abstract public function getMinimumResponseTime();


    /**
     * Checks if the contract has per incident options
     *
     * @return  boolean
     */
    abstract public function hasPerIncident();


    /**
     * Checks whether the contract has any incidents available to be redeemed.
     *
     * @param   integer $incident_type The type of incident
     * @return  boolean
     */
    abstract public function hasIncidentsLeft($incident_type = false);


    /**
     * Redeems an incident of the specified type for the specified issue.
     *
     * @param   integer $issue_id The issue
     * @param   integer $incident_type The type of incident
     * @return  integer 1 if the insert worked, -1 or -2 otherwise
     */
    abstract public function redeemIncident($issue_id, $incident_type);


    /**
     * un redeems an incident of the specified type for the specified issue.
     *
     * @param   integer $issue_id The issue
     * @param   integer $incident_type The type of incident
     * @return  integer 1 if the insert worked, -1 or -2 otherwise
     */
    abstract public function unRedeemIncident($issue_id, $incident_type);


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
     * @param   integer $issue_id The issue ID
     * @return  array An array containing the redeemed incident types
     */
    abstract public function getRedeemedIncidentDetails($issue_id);


    /**
     * Updates the incident counts
     *
     * @param   integer $issue_id The issue ID
     * @param   array $data An array of data containing which incident types to update.
     * @return  integer 1 if all updates were successful, -1 or -2 otherwise.
     */
    abstract public function updateRedeemedIncidents($issue_id, $data);

    /**
     * Returns status's considered 'active'.
     *
     * @return  array An array of active statuses
     */
    abstract static public function getActiveStatuses();


    /**
     * Method used to build the overall customer/contract profile
     *
     * @return  array The contract profile information
     */
    abstract public function getProfile();


    /**
     * Returns a message to be displayed to a customer on the top of the issue creation page.
     *
     * @return string
     */
    abstract public function getNewIssueMessage();


    /**
     * Method used to get the list of email addresses associated with the
     * contacts of this contract.
     *
     * @param   array   $options Any search options to apply
     * @param   boolean $no_email_in_title If the email address should be left out of the value portion of the result
     * @return  array The list of email addresses
     */
    abstract public function getContactEmailAssocList($options = false, $no_email_in_title = false);


    abstract public function getTitle();

    /**
     * Forces the contract to be reloaded. Useful only when contract is first saved.
     *
     */
    public function reload()
    {
        $this->load();
    }

    /**
     * Turns an array of contract object into a multi-dimensional array of contract details.
     *
     * @param   array $contracts An array of contract objects
     * @return  array An array of contract details
     */
    static public function getAllDetails($contracts)
    {
        $contracts_temp = array();
        foreach ($contracts as $contract_id => $contract) {
            $contracts_temp[$contract_id] = $contract->getDetails();
        }
        return $contracts_temp;
    }


    public function getCustomerID()
    {
        return $this->customer->getCustomerID();
    }

    /**
     * Returns the customer object this contract belongs too
     *
     * @return Customer
     */
    public function &getCustomer()
    {
        return $this->customer;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($new_status)
    {
        $this->status = $new_status;
    }

    public function getContractID()
    {
        return $this->contract_id;
    }

    public function getSupportID()
    {
        return $this->support_id;
    }

    public function getStartDate()
    {
        return $this->start_date;
    }

    public function getEndDate()
    {
        return $this->end_date;
    }

    public function getSupportLevelID()
    {
        return $this->support_level_id;
    }

    public function getSupportLevel()
    {
        return $this->support_level;
    }

    public function getPublicComment()
    {
        return $this->public_comment;
    }

    public function getPartnerID()
    {
        return $this->partner_id;
    }

    public function exists()
    {
        return $this->exists;
    }

    public function __toString()
    {
        $options = $this->getOptions(true);
        return "Contract\nID: " . $this->contract_id . "
            Start: " . $this->start_date . "
            End: " . $this->end_date . "\n" .
            "Options: " . $options['display'] . "\n" .
            "Exists: " . $this->exists . "\n";
    }

}
