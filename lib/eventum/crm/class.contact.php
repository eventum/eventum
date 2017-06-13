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
 * Abstract class representing a contact
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
     * Holds the database connection this object should use.
     *
     * @var resource
     */
    protected $connection;

    /**
     * The ID of the contact this object represents
     *
     * @var string
     */
    protected $contact_id;

    /**
     * The first_name of the contact
     *
     * @var string
     */
    protected $first_name;

    /**
     * The first_name of the contact
     *
     * @var string
     */
    protected $last_name;

    /**
     * The primary email address of this contact.
     *
     * @var string
     */
    protected $email;

    /**
     * The primary phone number of this contact.
     *
     * @var string
     */
    protected $phone;

    /**
     * If the contact is active. Inactive contacts should not be able to login
     *
     * @var bool
     */
    protected $active;

    /**
     * Contracts associated with this contact
     *
     * @var array
     */
    protected $associated_contracts = [];

    /**
     * Constructs the object representing this contact and loads contact data.
     *
     * @param CRM $crm
     * @param string  $contact_id
     * @throws ContactNotFoundException
     */
    public function __construct(CRM $crm, $contact_id)
    {
        $this->crm = $crm;
        $this->connection = &$crm->getConnection();
        $this->contact_id = $contact_id;

        // attempt to load the data
        $this->load();
    }

    /**
     * Returns an array of contracts the specified contact can access
     *
     * @param   array|bool $options An array of options that determine which contracts should be returned. For Legacy purposes, if this
     *                              is boolean then it will be used to indicate if only active contracts should be returned.
     * @return  Contract[] An array of support contracts this contact is allowed to access
     */
    abstract public function getContracts($options = false);

    /**
     * Returns an array of contracts ids the contact can access
     *
     * @param   array|bool $options An array of options that determine which contracts should be returned. For Legacy purposes, if this
     *                              is boolean then it will be used to indicate if only active contracts should be returned.
     * @return  int[] An array of support contract ids this contact is allowed to access
     */
    abstract public function getContractIDs($options = false);

    /**
     * Returns the customer ids that this contact can access
     *
     * @return int[]
     */
    abstract public function getCustomerIDs();

    /**
     * Returns the customer that this contact can access
     *
     * @return Customer[]
     */
    abstract public function getCustomers();

    /**
     * Returns true if associated with any active contracts, false otherwise. Optionally
     * takes a support level type. If the type is passed, true will only be a returned
     * if an active contract of the specified type exists.
     *
     * @param   array|bool $support_level_type
     * @return  bool
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
     * @param   int $issue_id The issue ID
     * @param   string $reason
     */
    abstract public function notifyIssueClosed($issue_id, $reason);

    /**
     * Loads contact info into the object
     *
     * @abstract
     * @throws ContactNotFoundException
     */
    abstract protected function load();

    /**
     * Returns true if the contact can access the specified contract, false otherwise
     *
     * @param   Contract $contract
     * @return  bool
     */
    abstract public function canAccessContract($contract);

    public function getContactID()
    {
        return $this->contact_id;
    }

    public function getName()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function getFirstName()
    {
        return $this->first_name;
    }

    public function getLastName()
    {
        return $this->last_name;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function getPhone()
    {
        return $this->phone;
    }

    public function __toString()
    {
        return "Contact\nID: " . $this->contact_id . "\n" .
            'Name: ' . $this->getName() . "\n";
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * Method used to notify the customer contact that a new issue was just
     * created and associated with his Eventum user.
     *
     * @param   int $issue_id The issue ID
     */
    abstract public function notifyNewIssue($issue_id);
}

class ContactNotFoundException extends CRMException
{
    public function __construct($contact_id, $message = null, Exception $previous = null)
    {
        if ($message !== null) {
            $message = "Contact '" . $contact_id . "' not found";
        }
        parent::__construct($message, 0, $previous);
    }
}
