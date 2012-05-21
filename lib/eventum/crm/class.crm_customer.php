<?php
/**
 * Abstract class representing a customer
 *
 * @author Bryan Alsdorf <balsdorf@gmail.com>
 */
abstract class CRM_Customer
{
    /**
     * Holds the parent CRM object.
     *
     * @var CRM
     */
    protected $crm;

    /**
     * Holds the database connection this object should use.
     *
     * @var resource
     */
    protected $connection;

    /**
     * If this customer exists.
     *
     * @var boolean
     */
    protected $exists;

    /**
     * The ID of the customer this object represents
     *
     * @var integer
     */
    protected $customer_id;

    /**
     * The name of the customer this object represents
     *
     * @var string
     */
    protected $name;

    /**
     * The country of the customer this object represents
     *
     * @var string
     */
    protected $country;

    /**
     * Constructs the customer object and loads customer and support option data.
     *
     * @param CRM $crm
     * @param integer $customer_id
     * @see Customer::load();
     */
    function __construct(CRM &$crm, $customer_id)
    {
        $this->crm = &$crm;
        $this->connection = &$crm->getConnection();
        $this->customer_id = $customer_id;

        // attempt to load the data
        $this->load();

    }

    /**
     * Convenience method for setting all customer data at once. This probably is only used by batch
     * scripts setting up customers.
     *
     * @param string $name
     * @param string $country
     */
    public function setData($name, $country)
    {
        $this->name = $name;
        $this->country = $country;
    }


    /**
     * Saves the object to the database. Returns true on success, PEAR_Error otherwise.
     *
     * @return  mixed True on success, PEAR_Error otherwise.
     */
    abstract public function save();


    /**
     * Loads customer information into the object. This method must set
     * this->exists to true if the customer exists and set it to false if
     * it doesn't exist.
     *
     * @see Customer::exists
     */
    abstract protected function load();


    /**
     * Returns a Contract object representing the contract for the
     * given contract ID. This should ONLY return contracts for the
     * current customer.
     *
     * @param   integer $contract_id
     * @return  Contract The Contract object for the given contract ID
     */
    abstract public function &getContract($contract_id);


    /**
     * Returns an array of contracts for this customer.
     *
     * @param   mixed Options An array of options that determine which contracts should be returned. For Legacy purposes, if this
     *                              is boolean then it will be used to indicate if only active contracts should be returned.
     * @return  Contract[] An array of Contract objects
     */
    abstract public function getContracts($options = false);


    /**
     * Returns a Contact object representing the contact for the
     * given contact ID. This should ONLY return contacts for the
     * current customer.
     *
     * @param   integer $contact_id
     * @return  Contact The Contact object for the given contact ID
     */
    abstract public function &getContact($contact_id);


    /**
     * Returns an array of contact objects for this customer.
     *
     * @return  Contact[] An array of Contact objects
     */
    abstract public function getContacts();

    /**
     * Returns various settings used when creating an issue
     *
     * @return array
     */
    abstract public function getSettings();

    /**
     * Returns an array of details about this customer
     *
     * @return  array
     */
    abstract public function getDetails();


    /**
     * Returns an array of the currently redeemed incident types for the issue.
     *
     * @param   integer $issue_id The issue ID
     * @return  array An array containing the redeemed incident types
     */
    public function getRedeemedIncidentDetails($issue_id)
    {
        $types = $this->crm->getIncidentTypes();
        $data = array();
        foreach ($types as $id => $title) {
            if ($this->isRedeemedIncident($issue_id, $id)) {
                $data[$id] = array(
                    'title' =>  $title,
                    'is_redeemed'   =>  1
                );
            }
        }
        return $data;
    }


    /**
     * Method used to get the overall statistics of issues in the system for a
     * given customer.
     *
     * @param   mixed $contract_ids
     * @return  array The customer related issue statistics
     */
    abstract public function getOverallStats($contract_ids);


    /**
     * Returns a log of activity for a given customer
     *
     * @param   integer $limit The max number of log items to return
     * @return  array
     */
    abstract public function getLog($limit = 200);

    public function getCustomerID()
    {
        return $this->customer_id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function exists()
    {
        return $this->exists;
    }


    public function getCRM()
    {
        return $this->crm;
    }

    public function getConnection()
    {
        return $this->connection;
    }


    /**
     * String representation of this object
     *
     * @return string
     */
    public function __toString()
    {
        return "ID: " . $this->customer_id . "\n" .
            "Name: " . $this->name . "\n";
    }
}