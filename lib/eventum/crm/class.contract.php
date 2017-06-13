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

/**
 * Abstract class representing a contract
 */
abstract class Contract
{
    /**
     * Holds the parent CRM object.
     *
     * @var CRM
     */
    protected $crm;

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
     * The ID of the contract this object represents
     *
     * @var string
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
     * The Support Level of this contract
     *
     * @var Support_Level
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
     * The options for this contract
     *
     * @var array
     */
    protected $options = [];

    /**
     * Constructs the contract object and loads contract data.
     *
     * @param CRM $crm
     * @param string $contract_id
     * @see Contract::load()
     */
    public function __construct(CRM $crm, $contract_id)
    {
        $this->crm = $crm;
        $this->connection = &$crm->getConnection();
        $this->contract_id = $contract_id;

        // attempt to load the data
        $this->load();
    }

    /**
     * Loads contract information into the object.
     *
     * @throws ContractNotFoundException
     */
    abstract protected function load();

    /**
     * Returns a contact object for the specified contact ID. This should ONLY return
     * the contact if it is associated with this contract.
     *
     * @param   string $contact_id
     * @throws  ContactNotFoundException
     * @return  Contact A contact object
     */
    abstract public function getContact($contact_id);

    /**
     * Returns an array contact objects for this contract
     *
     * @param   mixed $options an array of options that affect which contacts are returned
     * @return  Contact[]
     */
    abstract public function getContacts($options = false);

    /**
     * Returns the options associated with this contact.
     *
     * @return  array
     */
    abstract public function getOptions();

    /**
     * Returns the value of the specified option, or false if the option is not set
     *
     * @param   string $option_id The ID of the option
     * @return  mixed the value of the option or false
     */
    abstract public function getOption($option_id);

    /**
     * Returns if the contract has access to a given feature.
     *
     * @param   string  $feature The identifier for the feature
     * @return  bool
     */
    abstract public function hasFeature($feature);

    /**
     * Returns true if this contract is expired, false otherwise.
     *
     * @return  bool
     */
    abstract public function isExpired();

    /**
     * Returns true if this contract is active, false otherwise.
     *
     * @return  bool
     */
    abstract public function isActive();

    /**
     * Returns the maximum first response time for this contract in minutes. If
     * the issue ID is provided the issue will be examined for options which might
     * affect the response time such as Severity.
     *
     * @param bool|int $issue_id An array of issue details used to provide specified response time for (optional)
     * @return  int The response time in seconds
     */
    abstract public function getMaximumFirstResponseTime($issue_id = false);

    /**
     * Returns an array of details about this contract
     *
     * @return  array
     */
    abstract public function getDetails();

    /**
     * Returns true if the contract is of $type
     *
     * @param   mixed $type The type or array of types to look for
     * @return  bool
     */
    abstract public function isOfType($type);

    /**
     * Returns the minimum response time for a contract in seconds.
     *
     * @return  mixed the minimum response time or false
     */
    abstract public function getMinimumResponseTime();

    /**
     * Returns a summary of incidents available and usage. ex array("$type" => array("total" => 3, "used" => 2))
     *
     * @return array
     */
    abstract public function getIncidents();

    /**
     * Returns the total of incidents already redeemed in the given
     * contract ID.
     *
     * @param   int $incident_type The type of incident
     * @return  int The total of incidents already redeemed
     */
    abstract public function getIncidentUsage($incident_type);

    /**
     * Returns the total number of allowed incidents for the given support
     * contract ID.
     *
     * @param   int $incident_type The type of incident
     * @return  int The total number of incidents
     */
    abstract public function getTotalIncidents($incident_type);

    /**
     * Returns the number of incidents remaining for the given support
     * contract ID.
     *
     * @param   int $incident_type The type of incident
     * @return  int the number of incidents remaining
     */
    abstract public function getIncidentsRemaining($incident_type);

    /**
     * Checks if the contract has per incident options
     *
     * @return  bool
     */
    abstract public function hasPerIncident();

    /**
     * Checks whether the contract has any incidents available to be redeemed.
     *
     * @param bool|int $incident_type The type of incident
     * @return  bool
     */
    abstract public function hasIncidentsLeft($incident_type = false);

    /**
     * Redeems an incident of the specified type for the specified issue.
     *
     * @param   int $issue_id The issue
     * @param   int $incident_type The type of incident
     * @return  int 1 if the insert worked, -1 or -2 otherwise
     */
    abstract public function redeemIncident($issue_id, $incident_type);

    /**
     * un redeems an incident of the specified type for the specified issue.
     *
     * @param   int $issue_id The issue
     * @param   int $incident_type The type of incident
     * @return  int 1 if the insert worked, -1 or -2 otherwise
     */
    abstract public function unRedeemIncident($issue_id, $incident_type);

    /**
     * Checks whether the given issue ID was marked as a redeemed incident or
     * not.
     *
     * @param   int $issue_id The issue ID
     * @param   int $incident_type The type of incident
     * @return  bool
     */
    abstract public function isRedeemedIncident($issue_id, $incident_type);

    /**
     * Returns an array of the currently redeemed incident types for the issue.
     *
     * @param   int $issue_id The issue ID
     * @return  array An array containing the redeemed incident types
     */
    abstract public function getRedeemedIncidentDetails($issue_id);

    /**
     * Updates the incident counts
     *
     * @param   int $issue_id The issue ID
     * @param   array $data an array of data containing which incident types to update
     * @return  int 1 if all updates were successful, -1 or -2 otherwise
     */
    abstract public function updateRedeemedIncidents($issue_id, $data);

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
     * @param array|bool $options Any search options to apply
     * @param   bool $no_email_in_title If the email address should be left out of the value portion of the result
     * @return  array The list of email addresses
     */
    abstract public function getContactEmailAssocList($options = false, $no_email_in_title = false);

    /**
     * Returns a descriptive title
     *
     * @abstract
     * @return mixed
     */
    abstract public function getTitle();

    /**
     * Turns an array of contract object into a multi-dimensional array of contract details.
     *
     * @param   array $contracts An array of contract objects
     * @return  array An array of contract details
     */
    public static function getAllDetails($contracts)
    {
        $contracts_temp = [];
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
    public function getCustomer()
    {
        return $this->customer;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getContractID()
    {
        return $this->contract_id;
    }

    public function getStartDate()
    {
        return $this->start_date;
    }

    public function getEndDate()
    {
        return $this->end_date;
    }

    public function getSupportLevel()
    {
        return $this->support_level;
    }

    public function __toString()
    {
        return "Contract\nID: " . $this->contract_id . '
            Start: ' . $this->start_date . '
            End: ' . $this->end_date . "\n";
    }
}

class ContractNotFoundException extends CRMException
{
    public function __construct($contract_id, Exception $previous = null)
    {
        parent::__construct("Contract '" . $contract_id . "' not found", 0, $previous);
    }
}
