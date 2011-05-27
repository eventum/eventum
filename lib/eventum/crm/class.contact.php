<?php

/**
 * Abstract class representing a contact
 *
 * @author Bryan Alsdorf <balsdorf@gmail.com>
 */
abstract class Contact
{
    /**
     * Holds the parent CRM object.
     *
     * @var CRM
     */
    protected $crm;

    /**
     * Holds an instance of the customer object this contact belongs too.
     *
     * @var CRM_Customer
     */
    protected $customer;

    /**
     * Holds the database connection this object should use.
     *
     * @var resource
     */
    protected $connection;

    /**
     * If this contact exists
     *
     * @var boolean
     */
    public $exists;

    /**
     * The ID of the contact this object represents
     *
     * @var integer
     */
    protected $contact_id;

    /**
     * The name of the contact
     *
     * @var string
     */
    protected $name;

    /**
     * The primary email address of this contact.
     *
     * @var string
     */
    protected $email;

    /**
     * The primary phone number of thie contact.
     *
     * @var string
     */
    protected $phone;

    /**
     * Contracts associated with this contact
     *
     * @var array
     */
    protected $associated_contract_ids = array();

    /**
     * Constructs the object representing this contact and loads contact data.
     *
     * @param CRM $crm
     * @param integer  $contact_id
     */
    function __construct(CRM &$crm, $contact_id)
    {
        $this->crm = &$crm;
        $this->connection = &$crm->getConnection();
        $this->contact_id = $contact_id;

        // attempt to load the data
        $this->load();
    }


    /**
     * Convenience method for setting all contact data at once. This probably is only used by batch
     * scripts setting up customers.
     *
     * @param string $customer_id
     * @param string $name
     * @param string $email
     * @param string $phone
     */
    public function setData($customer_id, $name, $email, $phone)
    {
        $this->customer = &$this->crm->getCustomer($customer_id);
        $this->name = $name;
        $this->email = $email;
        $this->phone = $phone;
    }
    

    /**
     * Returns an array of contracts the specified contact can access
     *
     * @param   array|boolean $options An array of options that determine which contracts should be returned. For Legacy purposes, if this
     *                              is boolean then it will be used to indicate if only active contracts should be returned.
     * @return  array An array of support contracts this contact is allowed to access
     */
    abstract public function getContracts($options = false);


    /**
     * Returns true if associated with any active contracts, false otherwise. Optionally
     * takes a support level type. If the type is passed, true will only be a returned
     * if an active contract of the specified type exists.
     *
     * @param   array|boolean $support_level_type
     * @return  boolean
     */
    abstract public function hasActiveContract($support_level_type = false);


    /**
     * Method used to get the details associated with a customer contact.
     *
     * @return  array The contact details
     */
    abstract public function getDetails();


    /**
     * Method used to notify the customer contact that an existing issue
     * associated with him was just marked as closed.
     *
     * @param   integer $issue_id The issue ID
     * @return  void
     */
    abstract public function notifyIssueClosed($issue_id);

    /**
     * Stores the object in the database. Returns true on success, PEAR_error otherwise.
     *
     * @return mixed True on success, PEAR_error otherwise.
     */
    abstract public function save();


    // this method must set the $exists variable
    abstract protected function load();


    public function getContactID()
    {
        return $this->contact_id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function getPhone()
    {
        return $this->phone;
    }

    public function getCustomerID()
    {
        return $this->customer->getCustomerID();
    }

    /**
     * Returns a customer object
     *
     * @return Customer
     */
    public function &getCustomer()
    {
        return $this->customer;
    }

    public function exists()
    {
        return $this->exists;
    }


    public function __toString()
    {
        return "Contact\nID: " . $this->contact_id . "\n" .
            "Name: " . $this->name . "\n";
    }

}
